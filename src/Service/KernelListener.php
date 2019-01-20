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

            $this->fillMapping($metadata, $table, $result['reflection']);

            if (0 === \count($metadata->associationMappings)) {
                continue;
            }

            $relations[$class] = $metadata->associationMappings;
        }

        foreach ($relations as $table => $relation) {
            $main = $this->tableManager->getTable($table);

            foreach ($this->typeFilter($relation) as $data) {
                $reference = $this->tableManager->getTable($data['targetEntity']);

                if (null === $reference) {
                    continue;
                }

                $reflection  = new \ReflectionClass($table);
                $refMetadata = $relations[$data['targetEntity']];

                $prop   = $reflection->getProperty($data['fieldName']);
                $expose = $this->getFieldExpose($prop);
                $groups = Expose::DEFAULT_GROUPS;

                if ($expose) {
                    $groups = $expose->getGroups();
                }

                if (ClassMetadata::ONE_TO_MANY === $data['type']) {
                    $field = $refMetadata[$data['mappedBy']]['joinColumns'][0]['name'];

                    $main->addOneToManyField($reference, $this->toSnake($data['fieldName']), new FieldStrategy($field), $groups);

                    continue;
                }

                if (ClassMetadata::MANY_TO_ONE === $data['type']) {
                    $field = $data['joinColumns'][0]['name'];

                    $main->addManyToOneField($reference, $this->toSnake($data['fieldName']), new FieldStrategy($field), $groups);

                    continue;
                }

                if (ClassMetadata::MANY_TO_MANY === $data['type']) {
                    if ([] === $data['joinTable']) {
                        continue;
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

                    continue;
                }

                if (ClassMetadata::ONE_TO_ONE === $data['type']) {
                    $field = $data['joinColumns'][0]['name'];

                    $main->addOneToOneField($reference, $this->toSnake($data['fieldName']), new FieldStrategy($field), $groups);

                    continue;
                }
            }
        }
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

    private function fillMapping(ClassMetadata $metadata, Table $table, \ReflectionClass $reflection): void
    {
        foreach ($metadata->fieldMappings as $fieldMapping) {
            $name = $this->toSnake($fieldMapping['fieldName']);

            $this->mapping->addMap($table, $fieldMapping['columnName'], $name);

            $prop   = $reflection->getProperty($fieldMapping['fieldName']);
            $expose = $this->getFieldExpose($prop);

            if (!$expose) {
                $table->addSimpleField($fieldMapping['columnName']);

                continue;
            }

            $table->addSimpleField($fieldMapping['columnName'], $expose->getGroups());
        }
    }

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
}
