<?php
/**
 * campus_events_frontend comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2019 Brain Appeal GmbH
 *
 * @copyright 2019 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

namespace BrainAppeal\CampusEventsFrontend\Controller;

use BrainAppeal\CampusEventsConnector\Domain\Model\Event;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface;

/**
 * IndexController
 */
class EventController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * event repository
     *
     * @var \BrainAppeal\CampusEventsConnector\Domain\Repository\EventRepository
     */
    protected $eventRepository = null;

    /**
     * Inject a event repository to enable DI
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Repository\EventRepository $eventRepository
     */
    public function injectEventRepository(\BrainAppeal\CampusEventsConnector\Domain\Repository\EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    /**
     * action list
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function listAction()
    {
        $cObj = $this->configurationManager->getContentObject();
        $pidList = $this->settings['startingpoint'];
        $limit = (int) $this->settings['limit'];
        $timespan = $this->settings['timespan'];
        $events = $this->eventRepository->findListByPid($pidList, []);
        if ($timespan !== 'all') {
            $events = $this->filterListAfterTimespan($events,$timespan);
            $events = array_slice($events,0,$limit);
        }
        $this->view->assign('events', $events);
        $this->view->assign('contentData', $cObj->data);
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @throws \Exception
     */
    public function processRequest(RequestInterface $request, ResponseInterface $response)
    {
        try {
            parent::processRequest($request, $response);
        } catch (\Exception $exception) {
            $this->handleKnownExceptionsElseThrowAgain($exception);
        }
    }

    /**
     * @param \Exception $exception
     * @throws \Exception
     */
    private function handleKnownExceptionsElseThrowAgain(\Exception $exception)
    {
        $previousException = $exception->getPrevious();
        if (
            $this->actionMethodName === 'showAction'
            && $previousException instanceof \TYPO3\CMS\Extbase\Property\Exception
        ) {
            $this->redirect('list');
        } else {
            throw $exception;
        }
    }

    /**
     * action show
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Event $event
     *
     * @return void
     */
    public function showAction(\BrainAppeal\CampusEventsConnector\Domain\Model\Event $event) {
        $this->view->assign('event', $event);
    }

    /**
     * @param Event[] $events
     * @param string $timespan
     * @return Event[]
     */
    private function filterListAfterTimespan($events, $timespan) {
        $currentDate = new \DateTime();
        $filteredEvents = [];
        foreach ($events as $eventKey => $event) {
            $startDate = $event->getStartDate();
            $endDate = $event->getEndDate();
            if ($timespan === 'past') {
                if ($startDate <= $currentDate) {
                    $filteredEvents[] = $event;
                }
            } else if ($timespan === 'future') {
                if ($endDate >= $currentDate) {
                    $filteredEvents[] = $event;
                }
            }
        }
        usort($filteredEvents, function ($eventA, $eventB) {
           /** @var Event $eventA */
           /** @var Event $eventB */
           return $eventA->getStartDate() > $eventB->getStartDate();
        });
        return $filteredEvents;
    }

    protected function getErrorFlashMessage()
    {
        return false;
    }
}
