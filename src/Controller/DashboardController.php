<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Skill;
use App\Entity\SkillSet;
use App\Entity\User;
use App\Form\AvatarType;
use App\Form\ProfilType;
use App\Form\SkillCreationType;
use App\Form\UserKnownSkillType;
use App\Form\UserNewSkillType;
use App\Repository\MessageRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SkillSetRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\AvatarDealer;

/**
 * Class DashboardController
 * @package App\Controller
 * @Route("/dashboard", name="dashboard_")
 */
class DashboardController extends AbstractController
{
    /**
     * @Route("/index", name="index")
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param UserRepository $userRepository
     * @param MessageRepository $messageRepository
     * @return Response
     */
    public function index(
        EntityManagerInterface $entityManager,
        Request $request,
        UserRepository $userRepository,
        MessageRepository $messageRepository,
        ParticipantRepository $participantRepository
    ): Response {
        $user = $this->getUser();
        $sentMessages = $messageRepository->findBy(['sender' => $user]);
        $receivedMessages = $messageRepository->findBy(['receiver' => $user]);
        $contactBoard = [];
        foreach ($sentMessages as $sentMessage) {
            $contactBoard[] = $sentMessage->getReceiver()->getId();
        }
        foreach ($receivedMessages as $receivedMessage) {
            $contactBoard[] = $receivedMessage->getSender()->getId();
        }
        $contactBoardUnique = array_unique($contactBoard);
        $contacts = $userRepository->findBy(['id' => $contactBoardUnique], ['firstname' => 'ASC']);

        $userKnownSkillForm = $this->createForm(UserKnownSkillType::class, $user);
        $userKnownSkillForm->handleRequest($request);

        if (
            $userKnownSkillForm->isSubmitted()
            && $userKnownSkillForm->isValid()
        ) {
            $entityManager->flush();
            $this->addFlash('success', 'Your skills have well been updated.');
            return $this->redirectToRoute('dashboard_index', ['_fragment' => 'skills']);
        }

        $participationsAsProjectOwner = $participantRepository->findBy([
            'role' => Participant::ROLE_PROJECT_OWNER,
            'user' => $this->getUser(),
        ]);
        $participationsAsVolunteer = $participantRepository->findBy([
            'role' => Participant::ROLE_VOLUNTEER,
            'user' => $this->getUser(),
        ]);
        $participationsAsWaitingVolunteer = $participantRepository->findBy([
            'role' => Participant::ROLE_WAITING_VOLUNTEER,
            'user' => $this->getUser(),
        ]);
        $participationsAsProjectOwnerOnRequest = $participantRepository->findBy([
            'role' => Participant::ROLE_PROJECT_OWNER,
            'user' => $this->getUser(),
        ]);

        return $this->render('dashboard/index.html.twig', [
            'user_known_skill_form'           => $userKnownSkillForm->createView(),
            'contacts'                        => $contacts,
            'participationsAsProjectOwner'     => $participationsAsProjectOwner,
            'participationsAsVolunteer'        => $participationsAsVolunteer,
            'participationsAsWaitingVolunteer' => $participationsAsWaitingVolunteer,
        ]);
    }


    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @Route ("/newskills", name="new_skills", methods={"GET", "POST"})
     */
    public function newSkills(
        Request $request,
        SkillSetRepository $skillSetRepository,
        EntityManagerInterface $entityManager
    ): Response {

        $skill = new Skill();
        $creationSkillForm = $this->createForm(SkillCreationType::class, $skill);
        $creationSkillForm->handleRequest($request);
        $skillset = $skillSetRepository->findOneBy(['name' => 'Other']);

        if ($creationSkillForm->isSubmitted() && $creationSkillForm->isValid()) {
            $skill->addUser($this->getUser());
            $skill->setSkillSet($skillset);
            $entityManager->persist($skill);
            $entityManager->flush();

            return $this->redirectToRoute('dashboard_index',
                ['_fragment' => 'skills']
            );
        }

        return $this->render('dashboard/new_skills.html.twig', [
            'creationSkillForm' => $creationSkillForm->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="edit")
     * @param Request $request
     * @param User $user
     * @return Response
     */
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(ProfilType::class, $user, ['is_organization' => $user->getOrganization()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('dashboard_index', ['_fragment' => 'profile']);
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/editavatar/{id}", name="edit_avatar")
     * @param Request $request
     * @param User $user
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function editavatar(Request $request, User $user): Response
    {
        $hasBeenSubmitted = false;
        $form = $this->createForm(AvatarType::class, $this->getUser()->getAvatar());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $hasBeenSubmitted = true;
            return $this->redirectToRoute('dashboard_edit_avatar', [
                'id' => $user->getId(),
                'has_been_submitted' => $hasBeenSubmitted
            ]);
        }

        return $this->render('profile/edit_avatar.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * @Route("/saveavatar/{id}", name="save_avatar")
     * @param Request $request
     * @param User $user
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function saveavatar(Request $request, User $user, AvatarDealer $avatarDealer): Response
    {
        $imgData = $request->get('avatar');
        $imgName = $avatarDealer->saveUserNewAvatar($this->getUser(), $imgData);
        $user->getAvatar()->setName($imgName);
        $this->getDoctrine()->getManager()->flush();

        return $this->json(['id' => $user->getId()], Response::HTTP_OK);
    }


    /**
     * @param User $user
     * @return Response
     * @Route("/{id}", name="delete", methods={"POST"})
     */
    public function delete(User $user): Response
    {
        $user->setIsActive(false);
        $projectManager = $this->getDoctrine()->getManager();
        $projectManager->flush();
        return $this->redirectToRoute('app_logout');
    }
}
