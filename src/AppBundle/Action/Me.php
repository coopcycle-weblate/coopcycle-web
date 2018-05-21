<?php

namespace AppBundle\Action;

use AppBundle\Action\Utils\TokenStorageTrait;
use AppBundle\Entity\ApiUser;
use AppBundle\Entity\Delivery;
use AppBundle\Entity\Task;
use AppBundle\Entity\TaskList;
use Doctrine\Common\Persistence\ManagerRegistry as DoctrineRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

class Me
{
    use TokenStorageTrait;

    protected $doctrine;

    public function __construct(TokenStorageInterface $tokenStorage, DoctrineRegistry $doctrine, LoggerInterface $logger)
    {
        $this->tokenStorage = $tokenStorage;
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    /**
     * @Route(
     *   name="my_tasks_list",
     *   path="/me/task_list/{date}",
     *   defaults={
     *     "_api_resource_class"=TaskList::class,
     *     "_api_collection_operation_name"="my_tasks_list"
     *   }
     * )
     * @Method("GET")
     */
    public function taskListAction($date)
    {
        $date = new \DateTime($date);

        $taskList = $this->doctrine
            ->getRepository(TaskList::class)
            ->findOneBy([
                'courier' => $this->getUser(),
                'date' => $date
            ]);

        return $taskList;
    }

    /**
     * @Route(
     *   name="my_tasks",
     *   path="/me/tasks/{date}",
     *   defaults={
     *     "_api_resource_class"=Task::class,
     *     "_api_collection_operation_name"="my_tasks"
     *   }
     * )
     * @Method("GET")
     */
    public function tasksAction($date)
    {
        $date = new \DateTime($date);

        $taskList = $this->doctrine
            ->getRepository(TaskList::class)
            ->findOneBy([
                'courier' => $this->getUser(),
                'date' => $date
            ]);

        $tasks = [];
        if ($taskList) {
            $tasks = $taskList->getTasks();
        }

        return $tasks;
    }

    /**
     * @Route(path="/me", name="me",
     *  defaults={
     *   "_api_resource_class"=ApiUser::class,
     *   "_api_collection_operation_name"="me",
     * })
     * @Method("GET")
     */
    public function meAction()
    {
        return $this->getUser();
    }

    /**
     * @Route(path="/me/location", name="me_location")
     * @Method("POST")
     */
    public function locationAction(Request $request)
    {
        $data = [];
        $content = $request->getContent();
        if (!empty($content)) {
            $data = json_decode($content, true);
        }

        foreach ($data as $location) {
            // TODO Store location
        }

        return new JsonResponse([]);
    }
}
