<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Task;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

    /**
     * @Route("/status", name="status_")
     */
class StatusController extends AbstractController
{
    /**
     * @param Project $project
     * @param EntityManager $entityManager
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     * @Route("/closed/{id}", name="closed", methods={"POST"})
     */
    public function closed(Project $project, EntityManagerInterface $entityManager): Response
    {
        $project->setStatus(Project::STATUS_CLOSED);
        $entityManager->persist($project);
        $entityManager->flush();
        return $this->redirectToRoute('project_index');
    }

    /**
     * @param Task $task
     * @param EntityManager $entityManager
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     * @Route("/attributTask/{id}", name="attributTask", methods={"POST"})
     */
    public function attributTask(Task $task, EntityManagerInterface $entityManager): Response
    {
        $task->setStatus(Task::STATUS_TASK_IN_PROGRESS);
        $entityManager->persist($task);
        $entityManager->flush();
        return $this->redirectToRoute('project_show', [
            'id' => $task->getProject()->getId(),
        ]);
    }

    /**
     * @param Task $task
     * @param EntityManager $entityManager
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     * @Route("/achievedTask/{id}", name="achievedTask", methods={"POST"})
     */
    public function achievedTask(Task $task, EntityManagerInterface $entityManager): Response
    {
        $task->setStatus(Task::STATUS_TASK_ACHIEVED);
        $entityManager->persist($task);
        $entityManager->flush();
        return $this->redirectToRoute('project_show', [
            'id' => $task->getProject()->getId(),
        ]);
    }
}
