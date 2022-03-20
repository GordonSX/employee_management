<?php

namespace App\Controller;

use App\Entity\AssignedBonusGoals;
use App\Entity\MonthlyEmployeeCosts;
use App\Entity\Notifications;
use App\Entity\PaymentInfo;
use App\Entity\User;
use App\Entity\VacationRequests;
use App\Service\AcceptancePath;
use App\Service\NotificationService;
use DateTime;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AjaxCallsController extends AbstractController
{
    protected $acceptStatus = 'Wniosek zaakceptowano';
    protected $declineStatus = 'Wniosek odrzucono';

    /** @noinspection NullPointerExceptionInspection */
    public function getUserObject()
    {
        $username = $this->getUser()->getUserIdentifier();
        return  $this->getDoctrine()->getRepository(User::class)->findOneBy(['username'=>$username]);
    }

    /**
     * @Route("/ajax_changeStatusOfRequest")
     */
    public function ajax_changeStatusOfRequest(Request $request, NotificationService $notificationService)
    {
        if ($request->isXmlHttpRequest() || $request->query->get('showJson') === 1){
            $idOfRequest = $request->query->get('idOfRequest');
            $actionToPerform = $request->query->get('actionToPerform');
            $comment = $request->query->get('comment');
            $vacationRequest = $this->getDoctrine()->getRepository(VacationRequests::class)->find($idOfRequest);
            $entityManager = $this->getDoctrine()->getManager();
            if (isset($vacationRequest)) {
                $username = $vacationRequest->getUsername();
                $userObject = $this->getDoctrine()->getRepository(User::class)->findOneBy(['username'=>$username]);
                $dateFrom = $vacationRequest->getDateFrom()->format('d-m-Y');
                $dateTo = $vacationRequest->getDateTo()->format('d-m-Y');
                if (($actionToPerform==='accept') && (isset($userObject))){
                    $vacationDays = $vacationRequest->getNumberOfDays();
                    $currentVacationDays = $userObject->getNumberOfVacationDays();
                    if ($currentVacationDays-$vacationDays<0){
                        return new JsonResponse([
                            'status'    => 'ok',
                            'alert'     => 'danger'
                        ], 200);
                    }
                    $vacationRequest->setStatus($this->acceptStatus);
                    $vacationRequest->setComment($comment);
                    $entityManager->persist($vacationRequest);
                    $userObject->setNumberOfVacationDays($currentVacationDays-$vacationDays);
                    $entityManager->persist($userObject);
                    $entityManager->flush();
                    $additional_information = [
                        'pathName'      => 'list_of_requests',
                        'parameters'    => null
                    ];
                    $notificationService->setNotification($username, 'Twój przełożony zaakceptował wniosek urlopowy na okres od '.$dateFrom.' do '.$dateTo, $this->acceptStatus, $additional_information);
                }elseif (($actionToPerform==='dismiss') && (isset($userObject))){
                    $vacationRequest->setStatus($this->declineStatus);
                    $vacationRequest->setComment($comment);
                    $entityManager->persist($vacationRequest);
                    $entityManager->flush();
                    $additional_information = [
                        'pathName'      => 'list_of_requests',
                        'parameters'    => null
                    ];
                    $notificationService->setNotification($username, 'Twój przełożony odrzucił wniosek urlopowy na okres od '.$dateFrom.' do '.$dateTo, $this->declineStatus, $additional_information);
                }
                return new JsonResponse([
                    'status'    => 'ok',
                    'alert'     => 'success'
                ], 200);
            }
        }
        return $this->redirectToRoute('manage_requests');
    }

    /**
     * @Route("/ajax_paymentInfo")
     * @noinspection NullPointerExceptionInspection
     */
    public function ajax_paymentInfo(Request $request){
        $username = $this->getUser()->getUserIdentifier();
        $paymentInfoAboutLoggedInUser = $this->getDoctrine()->getRepository(PaymentInfo::class)->findOneBy(['username' => $username]);
        $monthlyEmployeeCosts = $this->getDoctrine()->getRepository(MonthlyEmployeeCosts::class)->find(1);
        /** @noinspection NotOptimalIfConditionsInspection */
        if (($request->isXmlHttpRequest() || $request->query->get('showJson') === 1) && ($paymentInfoAboutLoggedInUser!==null) && ($monthlyEmployeeCosts!==null)) {
            $jsonData = [
                'basic_salary'                  => $paymentInfoAboutLoggedInUser->getBasicSalary(),
                'bonus_salary'                  => $paymentInfoAboutLoggedInUser->getBonusSalary(),
                'pension_insurance'             => $monthlyEmployeeCosts->getPensionInsurance(),
                'disability_insurance'          => $monthlyEmployeeCosts->getDisabilityInsurance(),
                'insurance_in_case_of_illness'  => $monthlyEmployeeCosts->getInsuranceInCaseOfIllness(),
                'medical_insurance'             => $monthlyEmployeeCosts->getMedicalInsurance(),
                'advance_payment_for_PIT'      => $monthlyEmployeeCosts->getAdvancePaymentForPIT(),
            ];

            return new JsonResponse($jsonData);
        }

        return $this->redirectToRoute('admin');
    }

    /**
     * @Route("/ajax_setNotificationAsRead")
     * @noinspection NullPointerExceptionInspection
     */
    public function ajaxsetNotificationAsRead(Request $request, NotificationService $notificationService)
    {
        if ($request->isXmlHttpRequest() || $request->query->get('showJson') === 1) {
            $username = $this->getUser()->getUserIdentifier();
            $notification = $this->getDoctrine()->getRepository(Notifications::class)->findBy(['username'=>$username]);
            $entityManager = $this->getDoctrine()->getManager();
            $id = $request->query->get('id');
            $isItRead = $request->query->get('isItRead');
            foreach ($notification as $item) {
                if (is_string($id)){
                    if ($item->getId() === (int)$id) {
                        $item->setIsItRead($isItRead);
                        $entityManager->persist($item);
                        $entityManager->flush();
                    }
                }else {
                    foreach ($id as $iValue) {
                        if ($item->getId() === (int)$iValue) {
                            $item->setIsItRead($isItRead);
                            $entityManager->persist($item);
                            $entityManager->flush();
                        }
                    }
                }
            }
            $notificationsNumber = $notificationService->getNumberOfNotifications($username);
            return new JsonResponse([
                'status' => 'ok',
                'count' => $notificationsNumber,
            ], 200);
        }

        return $this->redirectToRoute('notifications');
    }

    /**
     * @Route("/ajax_removeNotification")
     * @noinspection NullPointerExceptionInspection
     */
    public function ajaxRemoveNotification(Request $request, NotificationService $notificationService)
    {
        if ($request->isXmlHttpRequest() || $request->query->get('showJson') === 1) {
            $username = $this->getUser()->getUserIdentifier();
            $notification = $this->getDoctrine()->getRepository(Notifications::class)->findBy(['username'=>$username]);
            $entityManager = $this->getDoctrine()->getManager();
            $id = $request->query->get('id');
            foreach ($notification as $item) {
                if (is_string($id)){
                    if ($item->getId() === (int)$id) {
                        $entityManager->remove($item);
                        $entityManager->flush();
                    }
                }else {
                    foreach ($id as $iValue) {
                        if ($item->getId() === (int)$iValue) {
                            $entityManager->remove($item);
                            $entityManager->flush();
                        }
                    }
                }
            }
            $notificationsNumber = $notificationService->getNumberOfNotifications($username);
            return new JsonResponse([
                'status' => 'ok',
                'count' => $notificationsNumber
            ], 200);
        }

        return $this->redirectToRoute('notifications');
    }

    /**
     * @Route("/ajax_changeStatusOfNotification")
     * @noinspection NullPointerExceptionInspection
     * @throws Exception
     */
    public function ajaxChangeStatusOfNotification(Request $request, AcceptancePath $acceptancePath, NotificationService $notificationService)
    {
        if ($request->isXmlHttpRequest() || $request->query->get('showJson') === 1) {
            $userObject = $this->getUserObject();
            $firstAndLastName = $userObject->getFirstName().' '.$userObject->getLastName();

            $message = $request->query->get('message');
            $period = new DateTime($request->query->get('period'));
            $action = $request->query->get('action');
            $comment = $request->query->get('databaseCommentArray');

            $assignedBonusGoals = '';
            if (($action === 'approveOrDecline') || ($action === 'acceptedOrDeclined')){
                $assignedBonusGoals = $this->getDoctrine()->getRepository(AssignedBonusGoals::class)->findOneBy(['period'=>$period, 'username'=>$userObject->getUserIdentifier()]);
                $assignedBonusGoals->setComment($comment);
            }elseif ($action === 'completion_percentage'){
                $assignedBonusGoals = $this->getDoctrine()->getRepository(AssignedBonusGoals::class)->findOneBy(['period'=>$period, 'username'=>$request->query->get('username')]);
                $assignedBonusGoals->setCompletionPercentage($comment);
                $notificationContent = 'Twój bezpośredni przełożony '.$request->query->get('acceptancePathMessage').' za okres '.$period->format('m-Y');
                $additional_information = [
                    'pathName'      => 'bonus_goals',
                    'parameters'    => [
                        'year'      => $period->format('Y'),
                        'month'     => $period->format('m'),
                        'day'       => $period->format('d')
                    ]
                ];
                $notificationService->setNotification($request->query->get('username'), $notificationContent, 'Nowa ocena karty celów', $additional_information);
            }
            $assignedBonusGoals->setProgress($message);
            $acceptancePath->setAcceptancePath($assignedBonusGoals->getUsername(), $firstAndLastName, $period, $request->query->get('acceptancePathMessage'));
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($assignedBonusGoals);
            $entityManager->flush();
            $currentDateAndTime = new DateTime('now');
            return new JsonResponse([
                'progress'          => $message,
                'periodDate'        => $assignedBonusGoals->getPeriod(),
                'firstAndLastName'  => $firstAndLastName,
                'currentDate'       => $currentDateAndTime->format('Y-m-d'),
                'currentTime'       => $currentDateAndTime->format('H:i:s'),
            ], 200);
        }

        return $this->redirectToRoute('bonus_goals');
    }
}
