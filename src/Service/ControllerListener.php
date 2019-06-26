<?php

namespace Mash\MysqlJsonSerializer\Service;

use Doctrine\Common\Annotations\Reader;
use FOS\RestBundle\Controller\Annotations\View;
use Mash\MysqlJsonSerializer\Wrapper\FieldWrapper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ControllerListener implements EventSubscriberInterface
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var FieldWrapper
     */
    private $wrapper;

    public function __construct(Reader $reader, FieldWrapper $wrapper)
    {
        $this->reader  = $reader;
        $this->wrapper = $wrapper;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onController',
        ];
    }

    public function onController(FilterControllerEvent $event)
    {
        $data = $event->getController();

        $controller = $data[0];
        $method     = $data[1];

        $reflection = new \ReflectionObject($controller);
        $method     = $reflection->getMethod($method);

        $annotations = $this->reader->getMethodAnnotations($method);
        $view        = $this->getView($annotations);

        if (!$view) {
            return;
        }

        $groups = $view->getSerializerGroups();

        if ([] === $groups || null === $groups) {
            return;
        }

        $this->wrapper->setGroups($groups);
    }

    private function getView(array $annotations): ?View
    {
        foreach ($annotations as $annotation) {
            if (!$annotation instanceof View) {
                continue;
            }

            return $annotation;
        }

        return null;
    }
}
