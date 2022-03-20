<?php

namespace App\Controller;

use App\Entity\AssignedBonusGoals;
use App\Entity\BonusGoals;
use App\Entity\Hierarchy;
use App\Entity\User;
use App\Entity\VacationRequests;
use App\Service\AcceptancePath;
use App\Service\NotificationService;
use DateTime;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AdminController extends AbstractController
{
    protected $approvedBonusGoalsMessage = 'Przekazano cele do oceny przełożonego';
    protected $declinedBonusGoalsMessage = 'Odrzucono kartę celów';
    protected $rejectedBonusGoalsMessage = 'Odrzucono ocenę';
    protected $createdBonusGoalsMessage = 'Przekazano do akceptacji pracownika';
    protected $acceptStatus = 'Wniosek zaakceptowano';
    protected $declineStatus = 'Wniosek odrzucono';

    /**
     * @throws Exception
     * @noinspection NullPointerExceptionInspection
     */
    public function __construct(Security $user, EntityManagerInterface $entityManager){
        $currentUserIdentifier = $user->getUser()->getUserIdentifier();
        $currentUser = $entityManager->getRepository(User::class)->findOneBy(['username' => $currentUserIdentifier]);
        if ($currentUser->getFirstTimeLoggingIn()){
            throw new Exception('User must change password first');
        }
    }

    /** @noinspection NullPointerExceptionInspection */
    public function getUserObject()
    {
        $currentUserIdentifier = $this->getUser()->getUserIdentifier();
        return  $this->getDoctrine()->getRepository(User::class)->findOneBy(['username'=>$currentUserIdentifier]);
    }

    public function createEditUserFormBuilder(Request $request, $newUser, $validationProgress, $isFieldMapped) : FormInterface{
        $usersArray = [];
        $hierarchy = $this->getDoctrine()->getRepository(Hierarchy::class)->findAllPositionsSmallerThanCurrentUser(2);
        foreach ($hierarchy as $position) {
            $users = $this->getDoctrine()->getRepository(User::class)->findBy(['position' => $position->getName()]);
            foreach ($users as $user){
                $firstAndLastName = $user->getFirstName().' '.$user->getLastName();
                $usersArray[$firstAndLastName] = $firstAndLastName;
            }
        }
        $positions = $this->getDoctrine()->getRepository(Hierarchy::class)->findAll();
        $positionsArray = [];
        foreach ($positions as $position){
            $nameOfPosition = $position->getName();
            $positionsArray[$nameOfPosition] = $nameOfPosition;
        }
        $form = $this->createFormBuilder($newUser, ['attr' => ['class' => $validationProgress]])
            ->add('first_name', TextType::class, [
                'attr'      => [
                    'placeholder'   => 'First Name',
                    'disabled'      => !$isFieldMapped
                ],
                'label'     => 'Imię',
                'mapped'    => $isFieldMapped,
            ])
            ->add('last_name', TextType::class, [
                'attr'      => [
                    'placeholder'   => 'Last Name',
                ],
                'label'     => 'Nazwisko',
            ])
            ->add('email', EmailType::class, [
                'attr'      => [
                    'placeholder'   => 'E-mail'
                ],
                'label'     => 'Adres mailowy'
            ])
            ->add('phone_number', NumberType::class, [
                'html5'     => true,
                'attr'      => [
                    'placeholder'   => 'Phone Number',
                ],
                'label'     => 'Numer telefonu'
            ])
            ->add('address', TextType::class, [
                'attr'      => [
                    'placeholder'   => 'Address'
                ],
                'label'     => 'Adres'
            ])
            ->add('city', TextType::class, [
                'attr'      => [
                    'placeholder'   => 'Miasto'
                ],
                'label'     => 'Miasto'
            ])
            ->add('postal_code', TextType::class, [
                'attr'      => [
                    'placeholder'   => 'Kod pocztowy'
                ],
                'label'     => 'Kod pocztowy'
            ])
            ->add('position', ChoiceType::class, [
                'attr'      => [
                    'placeholder'   => 'Stanowisko'
                ],
                'label'                     => 'Stanowisko',
                'choices'                   => $positionsArray,
            ])
            ->add('supervisor', ChoiceType::class, [
                'attr'      => [
                    'placeholder'   => 'Przełożony'
                ],
                'label'                     => 'Przełożony',
                'choices'                   => $usersArray,
            ])
            ->add('roles', ChoiceType::class, [
                'attr'      => [
                    'placeholder'   => 'Rola'
                ],
                'label'     => 'Rola',
                'mapped'        => false,
                'choices'                   => [
                    'Pracownik (brak możliwości zarządzania użytkownikami)'         => 0,
                    'Przełożony'        => 1
                ],
            ])
            ->add('date_of_employment', DateType::class, [
                'html5'         => false,
                'widget'        => 'single_text',
                'attr'          => [
                    'class'         => 'ui-datepicker',
                    'width'         => '20px',
                    'placeholder'   => 'Data zatrudnienia'
                ],
                'label'     => 'Data zatrudnienia'
            ])
            ->add('date_of_birth', DateType::class, [
                'html5'         => false,
                'widget'        => 'single_text',
                'attr'          => [
                    'class'         => 'ui-datepicker',
                    'width'         => '20px',
                    'placeholder'   => 'Data urodzin',
                    'data-inputmask' => "'alias': 'date'",
                    'disabled'      => !$isFieldMapped
                ],
                'label'     => 'Data urodzin',
                'mapped'    => $isFieldMapped
            ])
            ->add('type_of_contract', ChoiceType::class, [
                'attr'      => [
                    'placeholder'   => 'Typ umowy'
                ],
                'label'     => 'Typ umowy',
                'choices'                   => [
                    'Umowa na zastępstwo'      => 'Umowa na zastępstwo',
                    'Umowa na okres próbny'      => 'Umowa na okres próbny',
                    'Umowa na czas określony'      => 'Umowa na czas określony',
                    'Umowa na czas nieokreślony'      => 'Umowa na czas nieokreślony',
                    'Umowa na czas wykonywania określonej pracy'      => 'Umowa na czas wykonywania określonej pracy',
                ],
            ])
            ->add('bank_account_number', TextType::class, [
                'attr'      => [
                    'placeholder'   => 'Numer rachunku bankowego'
                ],
                'label'     => 'Numer rachunku bankowego',
            ])
            ->add('bank_account_name', TextType::class, [
                'attr'      => [
                    'placeholder'   => 'Nazwa banku'
                ],
                'label'   => 'Nazwa banku'
            ])
            ->add('number_of_vacation_days', NumberType::class, [
                'html5'     => true,
                'attr'      => [
                    'placeholder'   => 'Ilość dni urlopu'
                ],
                'label'   => 'Ilość dni urlopu'
            ])
            ->add('save', SubmitType::class, [
                'attr'      => [
                    'class'     => 'btn-outline-success form-control'
                ],
                'label'     => 'Zapisz'
            ])
            ->getForm();
        $form->handleRequest($request);

        if (!$isFieldMapped && !$form->isSubmitted()){
            $form->get('first_name')->setData($newUser->getFirstName());
            $form->get('date_of_birth')->setData($newUser->getDateOfBirth());
        }
        return $form;
    }

    public function createEditUser($form, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator, $newUser, $createOrEdit, $oldSupervisor): Response
    {
        if (($form->isSubmitted()) && ($form->isValid())) {
            if ($createOrEdit==='create'){
                $firstName = $form->get('first_name')->getData();
                $lastName = $form->get('last_name')->getData();
                $firstLetterOfName = strtolower(substr($firstName,0, -(strlen($firstName) - 1)));
                $search=array(' ', 'ć', 'ś', 'ą', 'ż', 'ó', 'ł', 'ś', 'ź', 'ń', 'ę', 'Ć', 'Ś', 'Ą', 'Ż', 'Ó', 'Ł', 'Ś', 'Ź', 'Ń', 'Ę');
                $replace=array('', 'c', 's', 'a', 'z', 'o', 'l', 's', 'z', 'n', 'e', 'c', 's', 'a', 'z', 'o', 'l', 's', 'z', 'n', 'e');
                $today = new DateTime('now');
                //pierwsza litera imienia, nazwisko, miesiąc i rok utworzenia konta
                $username = str_replace($search, $replace, $firstLetterOfName).strtolower(str_replace($search, $replace, $lastName)).$today->format('my');
                $newUser->setUsername($username);
                $newUser->setPassword($passwordHasher->hashPassword($newUser, 'NewPassword123'));
                $newUser->setProfilePicture('uploads/default.png');
                $newUser->setFirstTimeLoggingIn(true);
            }

            $role = $form->get('roles')->getData();

            if ($role === 0){
                $newUser->setRoles([]);
            }else{
                $newUser->setRoles(['ROLE_ADMIN']);
            }

            $bankAccountNumber = str_replace(' ', '', $form->get('bank_account_number')->getData());
            $newUser->setBankAccountNumber($bankAccountNumber);
            $entityManager = $this->getDoctrine()->getManager();
            if ($createOrEdit==='edit'){
                $oldSupervisorFirstAndLastName = explode(' ', $oldSupervisor);
                $oldSupervisorInstance = $this->getDoctrine()->getRepository(User::class)->findOneBy(['firstName' => $oldSupervisorFirstAndLastName[0], 'lastName' => $oldSupervisorFirstAndLastName[1]]);
                if ($oldSupervisorInstance!==null){
                    $newNumberOfSubordinates = $oldSupervisorInstance->getSubordinates() - 1;
                    $oldSupervisorInstance->setSubordinates($newNumberOfSubordinates);
                    $entityManager->persist($oldSupervisorInstance);
                }
            }

            $supervisor = $form->get('supervisor')->getData();
            $supervisorFirstAndLastName = explode(' ', $supervisor);
            $supervisorInstance = $this->getDoctrine()->getRepository(User::class)->findOneBy(['firstName'=>$supervisorFirstAndLastName[0], 'lastName'=>$supervisorFirstAndLastName[1]]);
            if ($supervisorInstance!==null) {
                $numberOfEmployees = $supervisorInstance->getSubordinates() + 1;
                $supervisorInstance->setSubordinates($numberOfEmployees);
                $entityManager->persist($supervisorInstance);
                $entityManager->persist($newUser);
                $entityManager->flush();
            }
            if ($createOrEdit === 'create'){
                $this->addFlash(
                    'notice',
                    'Nowy użytkownik został dodany'
                );
            }else{
                $this->addFlash(
                    'notice',
                    'Edycja użytkownika "'.$newUser->getFirstName().' '.$newUser->getLastName().'" zakończona pomyślnie'
                );
            }

            return $this->redirectToRoute('list_of_users');
        }

        if ($createOrEdit === 'edit'){
            $title = 'Edytuj użytkownika';
        }else{
            $title = 'Stwórz nowego użytkownika';
        }

        if (($form->isSubmitted()) && !($form->isValid())) {
            $errors = $validator->validate($newUser);
            $errorsArray = [];
            for ($i = 0; $i < $errors->count(); $i++){
                $error = $errors->get($i);
                $errorsArray[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->render('main/create_edit_user.html.twig', [
                'form'              => $form->createView(),
                'errorsArray'       => $errorsArray,
                'title'             => $title,
                'firstName'         => $newUser->getFirstName(),
                'lastName'          => $newUser->getLastName(),
                'dateOfBirth'       => $newUser->getDateOfBirth()
            ]);

        }

        return $this->render('main/create_edit_user.html.twig', [
            'form'              => $form->createView(),
            'title'             => $title
        ]);

    }

    /**
     * @Route(
     *     "/{_locale}/list_of_users",
     *     name="list_of_users",
     *     requirements={
     *         "_locale": "en|fr|de|pl",
     *     }
     * )
     */
    public function listOfUsers(): Response
    {
        $currentUser = $this->getUserObject();
        $firstAndLastNameOfCurrentUser = $currentUser->getFirstName().' '.$currentUser->getLastName();
        $users = $this->getDoctrine()->getRepository(User::class)->findBy(['supervisor' => $firstAndLastNameOfCurrentUser]);

        return $this->render('main/list_of_users.html.twig', [
            'users'         => $users
        ]);
    }

    /**
     * @Route(
     *     "/{_locale}/delete_user/{id}",
     *     name="delete_user",
     *     requirements={
     *         "_locale": "en|fr|de|pl",
     *     }
     * )
     */
    public function deleteUser(int $id): Response
    {
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        if (isset($user)){
            $firstName = $user->getFirstName();
            $lastName = $user->getLastName();
            $entityManager = $this->getDoctrine()->getManager();
            $supervisor = $user->getSupervisor();
            $supervisorFirstAndLastName = explode(' ', $supervisor);
            $supervisorInstance = $this->getDoctrine()->getRepository(User::class)->findOneBy(['firstName' => $supervisorFirstAndLastName[0], 'lastName' => $supervisorFirstAndLastName[1]]);
            if (isset($supervisorInstance)){
                $newNumberOfSubordinates = $supervisorInstance->getSubordinates() - 1;
                $supervisorInstance->setSubordinates($newNumberOfSubordinates);
                $entityManager->persist($supervisorInstance);
            }
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash(
                'notice',
                'Użytkownik "'.$firstName.' '.$lastName.'" został usunięty'
            );
        }else{
            $this->addFlash(
                'warning',
                'Brak użytkownika w bazie'
            );
        }

        return $this->redirectToRoute('list_of_users');
    }

    /**
     * @Route(
     *     "/{_locale}/create_user",
     *     name="create_user",
     *     requirements={
     *         "_locale": "en|fr|de|pl",
     *     }
     * )
     */
    public function createUser(Request $request, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): Response
    {
        $newUser = new User();
        $form = $this->createEditUserFormBuilder($request, $newUser, 'needs-validation', true);

        return $this->createEditUser($form, $passwordHasher, $validator, $newUser, 'create', null);
    }

    /**
     * @Route(
     *     "/{_locale}/edit_user/{id}",
     *     name="edit_user",
     *     requirements={
     *         "_locale": "en|fr|de|pl",
     *     }
     * )
     */
    public function editUser(int $id, Request $request, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): Response
    {
        $newUser = $this->getDoctrine()->getRepository(User::class)->find($id);
        if (isset($newUser)){
            $oldSupervisor = $newUser->getSupervisor();
            $form = $this->createEditUserFormBuilder($request, $newUser, 'needs-validation', false);
            return $this->createEditUser($form, $passwordHasher, $validator, $newUser, 'edit', $oldSupervisor);
        }

        return $this->redirectToRoute('list_of_users');

    }

    public function createEditGoalsFormBuilder($targets, $targetValues, $targetExpectedValues, $periodDate, $create, $bonusGoalsId, Request $request): FormInterface
    {
        $userObject = $this->getUserObject();
        $currentUserHierarchy = $this->getDoctrine()->getRepository(Hierarchy::class)->findOneBy(['name' => $userObject->getPosition()]);
        $wholeHierarchy = $this->getDoctrine()->getRepository(Hierarchy::class)->findAllPositionsGraterThanCurrentUser($currentUserHierarchy->getLevel());
        $assignToChoices = [];
        foreach ($wholeHierarchy as $level) {
            $level = $level->getName();
            $assignToChoices[$level] = $level;
        }

        $assignToData = [];
        if ($create===0){
            $bonusGoals = $this->getDoctrine()->getRepository(BonusGoals::class)->find($bonusGoalsId);
            $assignToDataString = $bonusGoals->getAssignedTo();
            $assignToData = explode(';', $assignToDataString);
        }

        $form = $this->createFormBuilder()
            ->add('target', CollectionType::class, [
                'entry_type' => TextType::class,
                'allow_add' => true,
                'prototype' => true,
                'data'      => $targets,
            ])
            ->add('target_value', CollectionType::class, [
                'allow_add' => true,
                'prototype' => true,
                'data'      => $targetValues,
                'entry_type'    => NumberType::class
            ])
            ->add('expected_value', CollectionType::class, [
                'allow_add' => true,
                'prototype' => true,
                'data'      => $targetExpectedValues,
                'entry_type'    => NumberType::class
            ])
            ->add('assigned_to', ChoiceType::class, [
                'placeholder'               => 'Wybierz grupę, do której chcesz przypisać cele',
                'choices'                   => $assignToChoices,
                'multiple'                  => true,
                'data'                      => $assignToData
            ])
            ->add('period_date', DateType::class, [
                'html5'         => false,
                'widget'        => 'single_text',
                'attr'          => [
                    'class'     => 'ui-datepicker',
                    'width'     => '20px'
                ],
                'placeholder'   => 'Podaj okres',
                'data'          => $periodDate
            ])
            ->add('save', SubmitType::class, [
                'attr'      => [
                    'class'     => 'btn btn-success'
                ],
                'label'     => 'Zapisz'
            ])
            ->getForm();
        $form->handleRequest($request);

        return $form;
    }

    public function createEditGoal($form, $userObject, AcceptancePath $acceptancePath, NotificationService $notificationService, $create, $bonusGoals){
        $allBonusGoals = $this->getDoctrine()->getRepository(BonusGoals::class)->findAll();
        $isGoalForThisPeriodSet = false;
        if ($create===1){
            $assignedToForm = $form->get('assigned_to')->getData();
            for($i = 0, $iMax = count($allBonusGoals); $i< $iMax; $i++){
                $assignedTo = $allBonusGoals[$i]->getAssignedTo();
                $assignedTo = explode(';', $assignedTo);
                foreach ($assignedTo as $jValue) {
                    foreach ($assignedToForm as $kValue) {
                        if (($jValue === $kValue) && $allBonusGoals[$i]->getPeriodDate()->format('m-Y') === $form->get('period_date')->getData()->format('m-Y')) {
                            $isGoalForThisPeriodSet = true;
                        }
                    }
                }
            }
        }
        if ($isGoalForThisPeriodSet && $create===1) {
            return $this->render('main/create_goals.html.twig', [
                'form'              => $form->createView(),
                'alert'             => true,
            ]);
        }

        $targets = $form->get('target')->getData();
        $targetValue = $form->get('target_value')->getData();
        $expectedValue = $form->get('expected_value')->getData();
        $allAssignedPositions = $form->get('assigned_to')->getData();
        $periodDate = $form->get('period_date')->getData();

        $targetsAndValues = [];
        $targetsAndExpectedValues = [];
        $targetsAndCompletionPercentage = [];

        for ($i = 0, $iMax = array_key_last($targets); $i <= $iMax; $i++) {
            if (isset($targets[$i])) {
                $targetsAndValues[$targets[$i]] = $targetValue[$i];
                $targetsAndExpectedValues[$targets[$i]] = $expectedValue[$i];
                $targetsAndCompletionPercentage[$targets[$i]] = '';
            }
        }

        $allAssignedPositions_string = '';
        $num = count($allAssignedPositions);
        $i = 0;
        foreach ($allAssignedPositions as $position) {
            if (++$i === $num) {
                $allAssignedPositions_string .= $position;
            } else {
                $allAssignedPositions_string .= $position . ';';
            }
        }
        $entityManager = $this->getDoctrine()->getManager();
        if ($create===1){
            $bonusGoals = new BonusGoals();
            $bonusGoals->setPeriodDate($periodDate);
        }
        $bonusGoals->setTarget($targetsAndValues);
        $bonusGoals->setExpectedValue($targetsAndExpectedValues);
        $bonusGoals->setAssignedTo($allAssignedPositions_string);
        $bonusGoals->setAddedBy($userObject->getUserIdentifier());

        $entityManager->persist($bonusGoals);

        if ($create===1){
            $allUsers = $this->getDoctrine()->getRepository(User::class)->findAll();
            $firstAndLastName = $userObject->getFirstName().' '.$userObject->getLastName();
            foreach ($allUsers as $user) {
                foreach ($allAssignedPositions as $position) {
                    if (($user->getPosition() === $position) && $user->getSupervisor() === $firstAndLastName) {
                        $assignedBonusGoals = new AssignedBonusGoals();
                        $assignedBonusGoals->setUsername($user->getUserIdentifier());
                        $assignedBonusGoals->setCompletionPercentage($targetsAndCompletionPercentage);
                        $assignedBonusGoals->setComment($targetsAndCompletionPercentage);
                        $assignedBonusGoals->setPeriod($periodDate);
                        $assignedBonusGoals->setProgress($this->createdBonusGoalsMessage);
                        $assignedBonusGoals->setBonusGoal($bonusGoals);
                        $entityManager->persist($assignedBonusGoals);
                        $notification = 'Twój bezpośredni przełożony przekazał Ci kartę celów biznesowych na okres ' . $periodDate->format('m-Y');
                        $additional_information = [
                            'pathName'      => 'bonus_goals',
                            'parameters'    => [
                                'year'      => $periodDate->format('Y'),
                                'month'     => $periodDate->format('m'),
                                'day'       => $periodDate->format('d')
                            ]
                        ];
                        $notificationService->setNotification($user->getUserIdentifier(), $notification, 'Otrzymałeś nową kartę celów', $additional_information);
                        $acceptancePath->setAcceptancePath($user->getUserIdentifier(), $user->getSupervisor(), $periodDate, 'przekazał kartę do oceny pracownika');
                    }
                }
            }
            $this->addFlash(
                'notice',
                'Nowa karta celów została utworzona'
            );
        }else{

            $assignedBonusGoals = $this->getDoctrine()->getRepository(AssignedBonusGoals::class)->findBy(['bonus_goal'=>$bonusGoals->getId()]);
            foreach ($assignedBonusGoals as $goal){
                $goal->setCompletionPercentage($targetsAndCompletionPercentage);
                $goal->setComment($targetsAndCompletionPercentage);
                $goal->setProgress($this->createdBonusGoalsMessage);
                $entityManager->persist($goal);
                $notification = 'Twój bezpośredni przełożony edytował kartę celów biznesowych za okres '.$goal->getPeriod()->format('m-Y');
                $additional_information = [
                    'pathName'      => 'bonus_goals',
                    'parameters'    => [
                        'year'      => $goal->getPeriod()->format('Y'),
                        'month'     => $goal->getPeriod()->format('m'),
                        'day'       => $goal->getPeriod()->format('d')
                    ]
                ];
                $firstAndLastName = $userObject->getFirstName().' '.$userObject->getLastName();
                $notificationService->setNotification($goal->getUsername(), $notification, 'Edytowano kartę celów', $additional_information);
                $acceptancePath->setAcceptancePath($goal->getUsername(),$firstAndLastName, $goal->getPeriod(), 'edytował kartę celów');
            }
            $this->addFlash(
                'notice',
                'Karta celów została edytowana'
            );
        }

        $entityManager->flush();

        return $this->redirectToRoute('list_of_goals');
    }

    /**
     * @Route(
     *     "/{_locale}/create_goals",
     *     name="create_goals",
     *     requirements={
     *         "_locale": "en|fr|de|pl",
     *     }
     * )
     * @noinspection NullPointerExceptionInspection
     * @throws \Exception
     */
    public function create_goals(Request $request, NotificationService $notificationService, AcceptancePath $acceptancePath): Response
    {
        $userObject = $this->getUserObject();

        $form = $this->createEditGoalsFormBuilder([''], [0], [0], null, 1, null, $request);

        if (($form->isSubmitted()) && ($form->isValid())){
            return $this->createEditGoal($form, $userObject, $acceptancePath,  $notificationService, 1, '');
        }

        if (($form->isSubmitted()) && !($form->isValid())){
            $target = $form->get('target')->getData();
            $target_value = $form->get('target_value')->getData();
            $expected_value = $form->get('expected_value')->getData();
            $period_date = $form->get('period_date')->getData();
            $form = $this->createEditGoalsFormBuilder($target, $target_value, $expected_value, $period_date, 1, null, $request);
            return $this->render('main/create_goals.html.twig', [
                'form'              => $form->createView(),
                'error'             => true
            ]);
        }

        return $this->render('main/create_goals.html.twig', [
            'form'              => $form->createView()
        ]);

    }

    /**
     * @Route(
     *     "/{_locale}/edit_goal/{id}",
     *     name="edit_goal",
     *     requirements={
     *         "_locale": "en|fr|de|pl",
     *     }
     * )
     * @noinspection NullPointerExceptionInspection
     */
    public function editGoal(int $id, Request $request, AcceptancePath $acceptancePath, NotificationService $notificationService): Response
    {
        $userObject = $this->getUserObject();
        $bonusGoals = $this->getDoctrine()->getRepository(BonusGoals::class)->find($id);
        if ($bonusGoals===null){
            return $this->redirectToRoute('list_of_goals');
        }
        $targets = [];
        $targetValues = [];
        $targetExpectedValues = [];
        foreach ($bonusGoals->getTarget() as $key => $item) {
            $targets[] = $key;
            $targetValues[] = $item;
        }
        foreach ($bonusGoals->getExpectedValue() as $item) {
            $targetExpectedValues[] = $item;
        }

        $form = $this->createEditGoalsFormBuilder($targets, $targetValues, $targetExpectedValues, $bonusGoals->getPeriodDate(), 0, $bonusGoals->getId(), $request);

        if (($form->isSubmitted()) && ($form->isValid())){
            return $this->createEditGoal($form, $userObject, $acceptancePath,  $notificationService, 0, $bonusGoals);
        }

        if (($form->isSubmitted()) && !($form->isValid())) {
            $target = $form->get('target')->getData();
            $target_value = $form->get('target_value')->getData();
            $expected_value = $form->get('expected_value')->getData();
            $this->addFlash(
                'warning',
                'Popraw zaznaczone błędy w formularzu aby kontynuować'
            );
            $form = $this->createEditGoalsFormBuilder($target, $target_value, $expected_value, $bonusGoals->getPeriodDate(), 0, $bonusGoals->getId(), $request);
        }
        return $this->render('main/edit_goals.html.twig', [
            'form'          => $form->createView()
        ]);
    }

    /**
     * @Route(
     *     "/{_locale}/assigned_goals",
     *     name="assigned_goals",
     *     requirements={
     *         "_locale": "en|fr|de|pl",
     *     }
     * )
     */
    public function assignedBonusGoals(): Response
    {
        $username = $this->getUser()->getUserIdentifier();
        $thisUser = $this->getDoctrine()->getRepository(User::class)->findOneBy(['username' => $username]);
        $firstAndLastName = $thisUser->getFirstName().' '.$thisUser->getLastName();
        $users = $this->getDoctrine()->getRepository(User::class)->findBy(['supervisor'=> $firstAndLastName]);
        $usernamesAndAssignedBonusGoals = [];
        $modalSaveFieldsDisplay = array();
        foreach ($users as $user) {
            $instanceOfCurrentUser = $this->getDoctrine()->getRepository(AssignedBonusGoals::class)->findBy(['username' => $user->getUserIdentifier()]);
            $usernamesAndAssignedBonusGoals[$user->getUserIdentifier()] = $instanceOfCurrentUser;
            $allAssignedBonusGoals = $this->getDoctrine()->getRepository(AssignedBonusGoals::class)->findBy(['username'=>$user->getUserIdentifier()], ['period' => 'ASC']);
            $temporaryArray = [];
            foreach ($allAssignedBonusGoals as $allAssignedBonusGoal) {
                if (($allAssignedBonusGoal->getProgress() !== $this->approvedBonusGoalsMessage) && ($allAssignedBonusGoal->getProgress() !== $this->rejectedBonusGoalsMessage)){
                    $temporaryArray[$allAssignedBonusGoal->getPeriod()->format('d-m-Y')] =  'disabled';
                }
            }
            $modalSaveFieldsDisplay[$user->getUserIdentifier()] = $temporaryArray;
        }
        return $this->render('main/assigned_bonus_goals.html.twig', [
            'firstAndLastName'                  => $firstAndLastName,
            'users'                             => $users,
            'usernamesAndAssignedBonusGoals'    => $usernamesAndAssignedBonusGoals,
            'saveFieldVisibility'               => $modalSaveFieldsDisplay,
            'rejectedBonusGoalsMessage'         => $this->rejectedBonusGoalsMessage,
            'declinedBonusGoalsMessage'         => $this->declinedBonusGoalsMessage
        ]);
    }

    /**
     * @Route(
     *     "/{_locale}/list_of_goals",
     *     name="list_of_goals",
     *     requirements={
     *         "_locale": "en|fr|de|pl",
     *     }
     * )
     */
    public function listOfGoals(): Response
    {
        $userObject = $this->getUserObject();
        $allBonusGoals = $this->getDoctrine()->getRepository(BonusGoals::class)->findBy(['added_by'=>$userObject->getUserIdentifier()], ['period_date'=>'ASC']);


        return $this->render('main/list_of_goals.html.twig', [
            'allBonusGoals'             => $allBonusGoals,
        ]);
    }

    /**
     * @Route(
     *     "/{_locale}/delete_goal/{year}/{month}/{day}",
     *     name="delete_goal",
     *     requirements={
     *         "_locale": "en|fr|de|pl",
     *     }
     * )
     * @throws \Exception
     */
    public function deleteGoal(string $year, string $month, string $day, NotificationService $notificationService): Response
    {
        $periodDate = '';
        $userObject = $this->getUserObject();
        $firstAndLastName = $userObject->getFirstName().' '.$userObject->getLastName();
        if ($year!=='' && $month!=='' && $day!==''){
            $month = sprintf('%02d', $month);
            $day = sprintf('%02d', $day);
            $periodDate = $year.'-'.$month.'-'.$day;
        }
        $periodDate = new DateTime($periodDate);
        $bonusGoalToDelete = $this->getDoctrine()->getRepository(BonusGoals::class)->findOneBy(
            [
                'added_by'=>$userObject->getUserIdentifier(),
                'period_date'=>$periodDate
            ]
        );

        if ($bonusGoalToDelete!==null){
            $assignedTo = $bonusGoalToDelete->getAssignedTo();
            $assignedTo = explode(';', $assignedTo);
            foreach ($assignedTo as $position) {
                $userWithGivenPosition = $this->getDoctrine()->getRepository(User::class)->findBy(['position'=>$position, 'supervisor'=>$firstAndLastName]);
                foreach ($userWithGivenPosition as $user) {
                    $notificationService->setNotification($user->getUserIdentifier(), 'Karta celów została usunięta', 'Karta celów została usunięta', null);
                }
            }
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($bonusGoalToDelete);
            $entityManager->flush();
        }

        return $this->redirectToRoute('list_of_goals');
    }

    /**
     * @Route(
     *     "/{_locale}/manage_requests",
     *     name="manage_requests",
     *     requirements={
     *         "_locale": "en|fr|de|pl",
     *     }
     * )
     */
    public function manageRequests(): Response
    {
        $userObject = $this->getUserObject();
        $firstAndLastName = $userObject->getFirstName().' '.$userObject->getLastName();
        $users = $this->getDoctrine()->getRepository(User::class)->findBy(['supervisor'=>$firstAndLastName]);
        $usersAndRequests = [];
        foreach ($users as $user){
            $vacationRequests = $this->getDoctrine()->getRepository(VacationRequests::class)->findBy(['username'=>$user->getUserIdentifier()]);
            $usersAndRequests[$user->getUserIdentifier()] = $vacationRequests;
        }

        return $this->render('main/manage_requests.html.twig', [
            'usersAndRequests'              => $usersAndRequests,
            'acceptStatus'                  => $this->acceptStatus,
            'declineStatus'                 => $this->declineStatus
        ]);
    }

}
