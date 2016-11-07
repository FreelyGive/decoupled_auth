<?php

namespace Drupal\decoupled_auth_event_test\EventSubscriber;

use Drupal\decoupled_auth\AcquisitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to AcquisitionEvent events and add information to the context.
 */
class DecoupledAuthEventTestSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquisitionEvent::PRE][] = array('setTestContextPre');
    $events[AcquisitionEvent::POST][] = array('setTestContextPost');
    return $events;
  }

  /**
   * Subscriber event.
   *
   * This method is called whenever the AcquisitionEvent::PRE event is
   * dispatched.
   *
   * @param AcquisitionEvent $event
   *   The acquisition event.
   */
  public function setTestContextPre(AcquisitionEvent $event) {
    $context = &$event->getContext();
    $context['testEventPre'] = TRUE;
  }

  /**
   * Subscriber event.
   *
   * This method is called whenever the AcquisitionEvent::POST event is
   * dispatched.
   *
   * @param AcquisitionEvent $event
   *   The acquisition event.
   */
  public function setTestContextPost(AcquisitionEvent $event) {
    $context = &$event->getContext();
    $context['testEventPost'] = TRUE;
  }

}
