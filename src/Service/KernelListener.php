<?php

namespace Mash\MysqlJsonSerializer\Service;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mash\MysqlJsonSerializer\Annotation\Expose;
use Mash\MysqlJsonSerializer\Annotation\Table as TableAnnotation;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\CrossReference\Pair;
use Mash\MysqlJsonSerializer\QueryBuilder\Field\CrossReference\Reference;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\FieldStrategy;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\JoinStrategy\ReferenceStrategy;
use Mash\MysqlJsonSerializer\QueryBuilder\Table\Table;
use Mash\MysqlJsonSerializer\Wrapper\Mapping;
use Mash\MysqlJsonSerializer\Wrapper\Type\CustomTypeInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class KernelListener.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class KernelListener implements EventSubscriberInterface
{
    private const SUPPORTED_TYPES = [
        ClassMetadata::MANY_TO_ONE  => true,
        ClassMetadata::ONE_TO_MANY  => true,
        ClassMetadata::MANY_TO_MANY => true,
        ClassMetadata::ONE_TO_ONE   => true,
    ];

    private $tableManager;

    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var Mapping
     */
    private $mapping;

    /**
     * KernelListener constructor.
     *
     * @param Reader            $reader
     * @param TableManager      $tableManager
     * @param RegistryInterface $registry
     * @param Mapping           $mapping
     */
    public function __construct(Reader $reader, TableManager $tableManager, RegistryInterface $registry, Mapping $mapping)
    {
        $this->tableManager = $tableManager;
        $this->registry     = $registry;
        $this->reader       = $reader;
        $this->mapping      = $mapping;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    /**
     * @param KernelEvent $event
     *
     * @throws \Doctrine\ORM\Mapping\MappingException
     * @throws \ReflectionException
     */
    public function onKernelRequest(KernelEvent $event)
    {
        unset($event); // not used

        /** @var EntityManager $manager */
        $manager   = $this->registry->getManager();
        $meta      = $manager->getMetadataFactory()->getAllMetadata();
        $relations = [];

        foreach ($this->annotationFilter($meta) as $class => $result) {
            /** @var ClassMetadata $metadata */
            $metadata = $result['metadata'];
            /** @var TableAnnotation $annotation */
            $annotation = $result['annotation'];

            $idMapping = $metadata->getFieldMapping('id');
            $table     = new Table($metadata->getTableName(), $annotation->getAlias(), $idMapping['columnName']);
            $this->tableManager->addTable($table, $class);

            $this->fillMapping($metadata, $table, $result['reflection'], $annotation);

            if (0 === \count($metadata->associationMappings)) {
                continue;
            }

            $relations[$class] = $metadata->associationMappings;
        }

        $this->processRelations($relations);
    }

    /**
     * @param array $relations
     *
     * @throws \ReflectionException
     */
    private function processRelations(array $relations)
    {
        foreach ($relations as $table => $relation) {
            $main = $this->tableManager->getTable($table);

            foreach ($this->typeFilter($relation) as $data) {
                $reference = $this->tableManager->getTable($data['targetEntity']);

                if (null === $reference) {
                    continue;
                }

                $reflection  = new \ReflectionClass($table);
                $refMetadata = $relations[$data['targetEntity']];

                try {
                    $prop = $reflection->getProperty($data['fieldName']);
                } catch (\ReflectionException $exception) {
                    $prop = $reflection->getParentClass()->getProperty($data['fieldName']);
                }

                $expose = $this->getFieldExpose($prop);
                $groups = Expose::DEFAULT_GROUPS;

                if ($expose) {
                    $groups = $expose->getGroups();
                }

                $this->createField($main, $data, $reference, $groups, $refMetadata);
            }
        }
    }

    private function createField(Table $main, array $data, Table $reference, array $groups, array $refMetadata)
    {
        if (ClassMetadata::ONE_TO_MANY === $data['type']) {
            $field = $refMetadata[$data['mappedBy']]['joinColumns'][0]['name'];

            $main->addOneToManyField($reference, $this->toSnake($data['fieldName']), new FieldStrategy($field), $groups);

            return;
        }

        if (ClassMetadata::MANY_TO_ONE === $data['type']) {
            $field = $data['joinColumns'][0]['name'];

            $main->addManyToOneField($reference, $this->toSnake($data['fieldName']), new FieldStrategy($field), $groups);

            return;
        }

        if (ClassMetadata::MANY_TO_MANY === $data['type']) {
            if ([] === $data['joinTable']) {
                return;
            }

            $joinTable = new Table($data['joinTable']['name'], $data['joinTable']['name'] . '_mtm');
            $strategy  = new ReferenceStrategy(
                new Reference(
                    new Pair($this->tableManager->getTable($data['sourceEntity']), 'adv_id'),
                    new Pair($joinTable, $data['joinTable']['joinColumns'][0]['name'])
                ),
                new Reference(
                    new Pair($reference, $reference->getIdField()),
                    new Pair($joinTable, $data['joinTable']['inverseJoinColumns'][0]['name'])
                )
            );

            $main->addManyToManyField($reference, $this->toSnake($data['fieldName']), $strategy, $groups);

            return;
        }

        if (!isset($data['joinColumns'][0])) {
            return;
        }

        // ONE TO ONE
        $field = $data['joinColumns'][0]['name'];

        $main->addOneToOneField($reference, $this->toSnake($data['fieldName']), new FieldStrategy($field), $groups);
    }

    private function typeFilter(array $data): \Generator
    {
        foreach ($data as $item) {
            if (!isset(self::SUPPORTED_TYPES[$item['type']])) {
                continue;
            }

            yield $item;
        }
    }

    private function getTableAnnotation(array $annotations): ?TableAnnotation
    {
        foreach ($annotations as $annotation) {
            if (!$annotation instanceof TableAnnotation) {
                continue;
            }

            return $annotation;
        }

        return null;
    }

    /**
     * @param array $data
     *
     * @throws \ReflectionException
     *
     * @return \Generator
     */
    private function annotationFilter(array $data): \Generator
    {
        foreach ($data as $metadata) {
            $class      = $metadata->getName();
            $reflection = new \ReflectionClass($class);

            $annotations = $this->reader->getClassAnnotations($reflection);
            if (0 === \count($annotations)) {
                continue;
            }

            $annotation = $this->getTableAnnotation($annotations);

            if (!$annotation) {
                continue;
            }

            yield $class => [
                'metadata'   => $metadata,
                'annotation' => $annotation,
                'reflection' => $reflection,
            ];
        }
    }

    /**
     * @param ClassMetadata    $metadata
     * @param Table            $table
     * @param \ReflectionClass $reflection
     * @param TableAnnotation  $annotation
     *
     * @throws \ReflectionException
     */
    private function fillMapping(ClassMetadata $metadata, Table $table, \ReflectionClass $reflection, TableAnnotation $annotation = null): void
    {
        foreach ($metadata->fieldMappings as $fieldMapping) {
            $this->exposeField($table, $reflection, $fieldMapping['columnName'], $fieldMapping['fieldName']);
        }

        if (!$annotation) {
            return;
        }

        $map = $annotation->getMap();

        if ([] === $map) {
            return;
        }

        foreach ($map as $field => $config) {
            if (\is_array($config)) {
                $this->processMapArray($table, $field, $config);

                continue;
            }

            $this->exposeField($table, $reflection, $field, $config);
        }
    }

    private function processMapArray(Table $table, string $fieldName, $config): void
    {
        if (!isset($config['route'])) {
            return;
        }

        $route = \explode('.', $config['route']);
        $last  = \array_pop($route);

        \preg_match('/(?P<table>[^\[\]]+)(\[(?P<property>[_a-z0-9]+)\])?/ui', $last, $matches);

        if ([] === $matches) {
            return;
        }

        $route[] = $matches['table'];

        $table->addJoinField($fieldName, $config['groups'] ?? Expose::DEFAULT_GROUPS)
            ->setType($config['type'])
            ->setProperty($matches['property'] ?? null)
            ->setRoute($route)
            ->setFilter($config['filter'] ?? [])
            ->setOrderBy($config['orderBy'] ?? 'id')
        ;
    }

    /**
     * @param Table            $table
     * @param \ReflectionClass $reflection
     * @param string           $fieldName
     * @param string           $alias
     *
     * @throws \ReflectionException
     */
    private function exposeField(Table $table, \ReflectionClass $reflection, string $fieldName, string $alias)
    {
        $name = $this->toSnake($alias);

        $this->mapping->addMap($table, $fieldName, $name);

        if (!$reflection->hasProperty($alias)) {
            return;
        }

        $prop   = $reflection->getProperty($alias);
        $expose = $this->getFieldExpose($prop);

        if (!$expose) {
            $table->addSimpleField($fieldName);

            return;
        }

        $type = $this->getType($expose->getType());
        $table->addSimpleField($fieldName, $expose->getGroups(), $type);
    }

    /**
     * @param \ReflectionProperty $property
     *
     * @return null|Expose
     */
    private function getFieldExpose(\ReflectionProperty $property): ?Expose
    {
        $annotations = $this->reader->getPropertyAnnotations($property);

        foreach ($annotations as $annotation) {
            if (!$annotation instanceof Expose) {
                continue;
            }

            return $annotation;
        }

        return null;
    }

    private function toSnake(string $name): string
    {
        return \ltrim(\mb_strtolower(\preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $name)), '_');
    }

    /**
     * @param null|string $class
     *
     * @throws \ReflectionException
     *
     * @return null|CustomTypeInterface
     */
    private function getType(?string $class): ?CustomTypeInterface
    {
        if (!$class) {
            return null;
        }

        if (!\class_exists($class)) {
            throw new \InvalidArgumentException('Class: ' . $class . ', is not exists. Invalid custom type class.');
        }

        $reflection = new \ReflectionClass($class);

        if (!$reflection->implementsInterface(CustomTypeInterface::class)) {
            throw new \InvalidArgumentException('Class: ' . $class . ' is not instance of ' . CustomTypeInterface::class);
        }

        return new $class();
    }
}
