<?php

namespace App\Controller;

use App\Entity\AssignedBonusGoals;
use App\Entity\BonusGoals;
use App\Entity\MonthlyEmployeeCosts;
use App\Entity\NonWorkingDays;
use App\Entity\Notifications;
use App\Entity\PaymentInfo;
use App\Entity\User;
use App\Entity\VacationRequests;
use DateInterval;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MainController extends AbstractController
{
    protected $approvedBonusGoalsMessage = 'Przekazano cele do oceny przełożonego';
    protected $declinedBonusGoalsMessage = 'Odrzucono kartę celów';
    protected $rejectedBonusGoalsMessage = 'Odrzucono ocenę';
    protected $acceptedBonusGoalsMessage = 'Zaakceptowano ocenę';
    protected $ratedBonusGoalsMessage = 'Ocena przekazana do akceptacji pracownika';
    protected $editedBonusGoalsMessage = 'Ocena ponownie przekazana do akceptacji pracownika';
    protected $acceptStatus = 'Wniosek zaakceptowano';
    protected $declineStatus = 'Wniosek odrzucono';

    /**
     * @noinspection NullPointerExceptionInspection
     */
    public function __construct(Security $user, EntityManagerInterface $entityManager){
        $currentUserIdentifier = $user->getUser()->getUserIdentifier();
        $thisUser = $entityManager->getRepository(User::class)->findOneBy(['username' => $currentUserIdentifier]);
        if ($thisUser->getFirstTimeLoggingIn()){
            /*throw new Exception('User must change password first');*/
            throw new InvalidPasswordException('User must change password first');
        }
    }


    /** @noinspection NullPointerExceptionInspection */
    public function getUserObject()
    {
        $currentUserIdentifier = $this->getUser()->getUserIdentifier();
        return  $this->getDoctrine()->getRepository(User::class)->findOneBy(['username'=>$currentUserIdentifier]);
    }

    /**
     * @Route(
     *     "/{_locale}/admin",
     *     name="admin",
     *     requirements={
     *         "_locale": "en|fr|de|pl",
     *     }
     * )
     */
    public function index(): Response
    {
        $userObject = $this->getUserObject();
        if (isset($userObject)) {
            $firstAndLastName = $userObject->getFirstName().' '.$userObject->getLastName();
            $userSubordinates = $this->getDoctrine()->getRepository(User::class)->findBy(['supervisor'=>$firstAndLastName]);
            $numberOfEmployees = count($userSubordinates);
        }else{
            $numberOfEmployees = 0;
        }
        $informationAboutUserPayment = $this->getDoctrine()->getRepository(PaymentInfo::class)->findOneBy(['username' => $userObject->getUserIdentifier()]);

        $monthlyEmployeeCosts = $this->getDoctrine()->getRepository(MonthlyEmployeeCosts::class)->find(1);

        return $this->render('main/index.html.twig', [
            'numberOfEmployees'                     => $numberOfEmployees,
            'informationAboutUserPayment'           => $informationAboutUserPayment,
            'monthlyEmployeeCosts'                  => $monthlyEmployeeCosts,
        ]);
    }

    /**
     * @Route(
     *     "/{_locale}/bonus_goals/{year}/{month}/{day}",
     *     name="bonus_goals",
     *     requirements={
     *         "_locale": "en|fr|de|pl",
     *     }
     * )
     */
    public function bonus_goals(string $year = '', string $month = '', string $day = ''): Response
    {
        $periodDate = '';
        if ($year!=='' && $month!=='' && $day!==''){
            $month = sprintf('%02d', $month);
            $day = sprintf('%02d', $day);
            $periodDate = $day.'-'.$month.'-'.$year;
        }
        $userObject = $this->getUserObject();
        $userPosition = $userObject->getPosition();
        $allBonusGoals = $this->getDoctrine()->getRepository(BonusGoals::class)->findAll();
        $loopIndex = 0;
        $bonusGoalsMatchingUserPosition = [];
        //Loop through them and put into array only those which match user position
        foreach ($allBonusGoals as $goal){
            $array = explode(';',$goal->getAssignedTo());
            foreach ($array as $item){
                if (!empty($userPosition) && $item===$userPosition){
                    $id = $goal->getId();
                    $bonusGoalsMatchingUserPosition[$loopIndex] = $this->getDoctrine()->getRepository(BonusGoals::class)->find($id);
                    $loopIndex++;
                }
            }
        }

        //Get all
        $allAssignedBonusGoals = $this->getDoctrine()->getRepository(AssignedBonusGoals::class)->findBy(['username'=>$userObject->getUserIdentifier()], ['period' => 'ASC']);
        $modalSaveFieldsDisplay = array();
        foreach ($allAssignedBonusGoals as $allAssignedBonusGoal) {
            if (($allAssignedBonusGoal->getProgress() === $this->approvedBonusGoalsMessage) || ($allAssignedBonusGoal->getProgress() === $this->declinedBonusGoalsMessage) || ($allAssignedBonusGoal->getProgress() === $this->acceptedBonusGoalsMessage) || ($allAssignedBonusGoal->getProgress() === $this->rejectedBonusGoalsMessage)){
                /** @noinspection NullPointerExceptionInspection */
                $modalSaveFieldsDisplay[$allAssignedBonusGoal->getPeriod()->format('d-m-Y')] = 'disabled';
            }
        }

        return $this->render('main/bonus_goals.html.twig', [
            'bonusGoalsMatchingUserPosition'            => $bonusGoalsMatchingUserPosition,
            'allAssignedBonusGoals'                     => $allAssignedBonusGoals,
            'saveFieldVisibility'                       => $modalSaveFieldsDisplay,
            'redirectFromNotification'                  => $periodDate,
            'ratedBonusGoalsMessage'                    => $this->ratedBonusGoalsMessage,
            'editedBonusGoalsMessage'                   => $this->editedBonusGoalsMessage
        ]);
    }

    /**
     * @Route(
     *     "/{_locale}/notifications",
     *     name="notifications",
     *     requirements={
     *         "_locale": "en|fr|de|pl",
     *     }
     * )
     * @noinspection NullPointerExceptionInspection
     */
    public function notifications(): Response
    {

        $currentUserIdentifier = $this->getUser()->getUserIdentifier();
        $notifications = $this->getDoctrine()->getRepository(Notifications::class)->findBy(['username' => $currentUserIdentifier]);

        return $this->render('main/notifications.html.twig', [
            'notifications'             => $notifications,
        ]);
    }

    /**
     * @Route(
     *     "/{_locale}/vacation_requests",
     *     name="vacation_requests",
     *     requirements={
     *         "_locale": "en|fr|de|pl",
     *     }
     * )
     */
    public function vacationRequests(Request $request, ValidatorInterface $validator): Response
    {
        $users = $this->getDoctrine()->getRepository(User::class)->findAll();
        $userObject = $this->getUserObject();
        $choices = [
            'Urlop wypoczynkowy'    => 'Urlop wypoczynkowy',
            'Urlop okolicznościowy' => 'Urlop okolicznościowy',
            'Urlop szkoleniowy'     => 'Urlop szkoleniowy',
            'Urlop na żądanie'      => 'Urlop na żądanie',
            'Urlop bezpłatny'       => 'Urlop bezpłatny'
        ];
        $vacationRequest = new VacationRequests();
        $form = $this->createFormBuilder($vacationRequest)
            ->add('type_of_request', ChoiceType::class, [
                'label'         => 'Wybierz rodzaj wniosku',
                'choices'                   => $choices,
            ])
            ->add('date_from', DateType::class, [
                'html5'                     => false,
                'widget'                    => 'single_text',
                'attr'      => [
                    'class'     => 'ui-datepicker',
                    'placeholder'   => 'Podaj okres',
                ],
                'label'             => 'Data rozpoczęcia urlopu',
            ])
            ->add('date_to', DateType::class, [
                'html5'                     => false,
                'widget'                    => 'single_text',
                'attr'      => [
                    'class'     => 'ui-datepicker',
                    'placeholder'   => 'Podaj okres'
                ],
                'label'             => 'Data zakończenia urlopu'
            ])
            ->add('replacement_user', TextType::class, [
                'attr'      => [
                    'class'             => 'replacementPerson',
                    'data-bs-toggle'    => 'modal',
                    'data-bs-target'    => '#exampleModal',
                    'maxlength'         => 0,
                    'placeholder'       => 'Osoba zastępująca'
                ],
                'label'                 => 'Osoba zastępująca'
            ])
            ->add('save', SubmitType::class, [
                'attr'      => [
                    'class'     => 'form-control btn btn-outline-success'
                ],
                'label'     => 'Zapisz'
            ])
            ->getForm();
        $form->handleRequest($request);

        /*$addDays = new NonWorkingDays();
        $year = '2030';
        $addDays->setYear($year);
        $addDays->setDays([ $year.'-01-01', $year.'-01-06', $year.'-04-21', $year.'-04-22', $year.'-05-01', $year.'-05-03', $year.'-06-09', $year.'-06-20', $year.'-08-15', $year.'-11-01', $year.'-11-11', $year.'-12-25', $year.'-12-26']);
        $em = $this->getDoctrine()->getManager();
        $em->persist($addDays);
        $em->flush();*/
        $nonWorkingDays = $this->getDoctrine()->getRepository(NonWorkingDays::class)->findAll();
        if ($form->isSubmitted() && $form->isValid()){
            $dateFrom = $form->get('date_from')->getData();
            $dateTo = $form->get('date_to')->getData();
            if ($dateTo<$dateFrom){
                $this->addFlash(
                    'warning',
                    'Data zakończenia urlopu nie może być wcześniejsza niż data jego rozpoczęcia!'
                );
                return $this->render('main/vacation_requests.html.twig', [
                    'users'                     => $users,
                    'form'                      => $form->createView(),
                    'nonWorkingDays'            => $nonWorkingDays
                ]);
            }
            $eachDay = clone $dateFrom;
            $time = date_diff($dateTo,$dateFrom);
            /** @noinspection PhpWrongStringConcatenationInspection */
            $interval = $time->format('%a') + 1;
            $newInterval = $interval;
            for ($i=0; $i<$interval; $i++){
                if ($i!==0){
                    $eachDay->add(new DateInterval('P1D'));
                    if (($eachDay->format('D') === 'Sat') || ($eachDay->format('D') === 'Sun')) {
                        $newInterval--;
                    }
                    foreach ($nonWorkingDays as $nonWorkingDay){
                        foreach ($nonWorkingDay->getDays() as $day) {
                            if (($eachDay->format('Y-m-d') === $day) && ($eachDay->format('D') !== 'Sat') && ($eachDay->format('D') !== 'Sun')) {
                                $newInterval--;
                            }
                        }
                    }
                }

            }
            if ($newInterval<= $userObject->getNumberOfVacationDays()){
                $vacationRequest->setUsername($userObject->getUserIdentifier());
                $vacationRequest->setStatus('Wniosek przekazany do akceptacji');
//                $userObject->setNumberOfVacationDays($userObject->getNumberOfVacationDays()-$newInterval);
                $vacationRequest->setNumberOfDays($newInterval);
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($vacationRequest);
                $entityManager->flush();
                $this->addFlash(
                    'notice',
                    'Wniosek został przesłany do akceptacji przełożonego'
                );
            }else{
                $this->addFlash(
                    'warning',
                    'Wniosek nie został wysłany, nie masz wystarczająco dużo dni urlopu!'
                );
            }
        }

        if (($form->isSubmitted()) && !($form->isValid())) {
            $errors = $validator->validate($vacationRequest);
            $errorsArray = [];
            for ($i = 0; $i < $errors->count(); $i++){
                $error = $errors->get($i);
                $errorsArray[$error->getPropertyPath()] = $error->getMessage();
            }
            if ($form->get('date_from')->getData() === null) {
                $errorsArray['date_from'] = 'Niepoprawny format danych';
            }
            if ($form->get('date_to')->getData() === null) {
                $errorsArray['date_to'] = 'Niepoprawny format danych';
            }
            return $this->render('main/vacation_requests.html.twig', [
                'users'                     => $users,
                'form'                      => $form->createView(),
                'nonWorkingDays'            => $nonWorkingDays,
                'errorsArray'               => $errorsArray
            ]);

        }

        return $this->render('main/vacation_requests.html.twig', [
            'users'                     => $users,
            'form'                      => $form->createView(),
            'nonWorkingDays'            => $nonWorkingDays
        ]);
    }

    /**
     * @Route(
     *     "/{_locale}/list_of_requests",
     *     name="list_of_requests",
     *     requirements={
     *         "_locale": "en|fr|de|pl",
     *     }
     * )
     */
    public function listOfRequests(): Response
    {
        $userObject = $this->getUserObject();
        $username = $userObject->getUserIdentifier();

        $vacationRequests = $this->getDoctrine()->getRepository(VacationRequests::class)->findBy(['username'=>$username]);

        return $this->render('main/vacation_requests_list.html.twig', [
            'vacationRequests'              => $vacationRequests
        ]);
    }
}