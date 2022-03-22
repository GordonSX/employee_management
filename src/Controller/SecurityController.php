<?php

namespace App\Controller;

use App\Entity\Hierarchy;
use App\Entity\MonthlyEmployeeCosts;
use App\Entity\User;
use DateTime;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($this->isGranted('IS_AUTHENTICATED_FULLY')){
            return $this->redirectToRoute('admin');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error'     => $error
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    public function createTestUsers(UserPasswordHasherInterface $passwordHasher): void
    {
        $data = [
            ['tadmin0322', ["ROLE_ADMIN"], 'tadmin@company.com', 'Admin', 'Rank 1', 2],
            ['tuser10322', [], 'tuser1@company.com', 'User1', 'Rank 2', 0],
            ['tuser20322', [], 'tuser2@company.com', 'User2', 'Rank 3', 0]
        ];
        $entityManager = $this->getDoctrine()->getManager();
        $rank1 = new Hierarchy();
        $rank1->setName('Rank 1');
        $rank1->setLevel(3);
        $entityManager->persist($rank1);

        $rank2 = new Hierarchy();
        $rank2->setName('Rank 2');
        $rank2->setLevel(2);
        $entityManager->persist($rank2);

        $rank3 = new Hierarchy();
        $rank3->setName('Rank 3');
        $rank3->setLevel(1);
        $entityManager->persist($rank3);
        $entityManager->flush();

        $employeeCosts = new MonthlyEmployeeCosts();
        $employeeCosts->setPensionInsurance(9.76);
        $employeeCosts->setDisabilityInsurance(1.5);
        $employeeCosts->setMedicalInsurance(7.77);
        $employeeCosts->setInsuranceInCaseOfIllness(2.45);
        $employeeCosts->setAdvancePaymentForPIT(5.69);
        $entityManager->persist($employeeCosts);
        $entityManager->flush();


        foreach ($data as $item) {
            $user = new User();
            $user->setUsername($item[0]);
            if ($item[0]!=='tadmin0322'){
                $user->setSupervisor('Test Admin');
            }else{
                $user->setSupervisor('Higher Admin');
            }
            $user->setPassword($passwordHasher->hashPassword($user, 'NewPassword123!'));
            $user->setRoles($item[1]);
            $user->setEmail($item[2]);
            $user->setFirstName('Test');
            $user->setLastName($item[3]);
            $user->setPhoneNumber(999888999);
            $user->setProfilePicture('uploads/default.png');
            $user->setAddress('Lwowska 33');
            $user->setPosition($item[4]);
            $user->setDateOfEmployment(new DateTime('now'));
            $user->setDateOfBirth(new DateTime('1990-01-01'));
            $user->setSubordinates($item[5]);
            $user->setTypeOfContract('Umowa o pracę na czas nieokreślony');
            $user->setBankAccountNumber('PL10105000997603123456789123');
            $user->setBankAccountName('PEKAOOO');
            $user->setNumberOfVacationDays(26);
            $user->setFirstTimeLoggingIn(true);
            $user->setPostalCode('05-555');
            $user->setCity('Warszawa');

            $entityManager->persist($user);
            $entityManager->flush();
        }

    }

    /**
     * @Route("/setup", name="setup")
     */
    public function setup(UserPasswordHasherInterface $passwordHasher): Response
    {
        $users = $this->getDoctrine()->getRepository(User::class)->findAll();
        if ($users!==null){
            foreach ($users as $user){
                if ($user->getUserIdentifier()==='tadmin0322'){
                    $this->addFlash('warning', 'Testowy użytkownik został już utworzony!');
                    return $this->redirectToRoute('app_login');
                }
            }
        }
        $this->createTestUsers($passwordHasher);
        $this->addFlash('notice', 'Pomyślnie utworzono testowych użytkowników. Login administratora: tadmin0322, hasło: NewPassword123!');
        return $this->redirectToRoute('app_login');
    }

    /**
     * @Route(
     *     "/{_locale}/change_password",
     *     name="change_password",
     *     requirements={
     *         "_locale": "en|fr|de|pl",
     *     }
     * )
     * @noinspection NotOptimalRegularExpressionsInspection
     */
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createFormBuilder()
            ->add('username', TextType::class, [
                'attr'      => [
                    'placeholder'   => 'Nazwa użytkownika',
                    'disabled'      => 'disabled'
                ],
                'label'     => 'Nazwa użytkownika',
            ])
            ->add('oldPassword', PasswordType::class, [
                'attr'      => [
                    'placeholder'   => 'Hasło',
                ],
                'label'     => 'Hasło',
            ])
            ->add('newPassword', PasswordType::class, [
                'attr'      => [
                    'placeholder'   => 'Nowe hasło',
                ],
                'label'     => 'Nowe hasło',
            ])
            ->add('confirmNewPassword', PasswordType::class, [
                'attr'      => [
                    'placeholder'   => 'Potwierdź nowe hasło',
                ],
                'label'     => 'Potwierdź nowe hasło',
            ])->getForm();

        $form->handleRequest($request);

        /** @noinspection NullPointerExceptionInspection */
        $username = $this->getUser()->getUserIdentifier();


        if (($form->isSubmitted()) && ($form->isValid())){
            $oldPassword = $form->get('oldPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();
            $confirmNewPassword = $form->get('confirmNewPassword')->getData();
            $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['username' => $username]);
            if ((isset($user)) && ($passwordHasher->isPasswordValid($user, $oldPassword))){
                if ($newPassword===$confirmNewPassword){
                    $uppercase = preg_match('@[A-Z]@', $newPassword);
                    $lowercase = preg_match('@[a-z]@', $newPassword);
                    $number    = preg_match('@[0-9]@', $newPassword);
                    $specialChars = preg_match('@[^\w]@', $newPassword);
                    if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($newPassword) < 8) {
                        $this->addFlash(
                            'warning',
                            'Hasło powinno zawierać co najmniej jedną wielką literę, jedną cyfrę, jeden znak 
                            specjalny i nie powinno być krótsze niż 8 znaków'
                        );
                    }elseif ($passwordHasher->isPasswordValid($user, $newPassword)){
                        $this->addFlash(
                            'warning',
                            'Nowe hasło nie może być takie samo jak poprzednie!'
                        );
                    }else{
                        $entityManager = $this->getDoctrine()->getManager();
                        $user->setPassword($passwordHasher->hashPassword($user,$newPassword));
                        $user->setFirstTimeLoggingIn(false);
                        $entityManager->persist($user);
                        $entityManager->flush();
                        $this->addFlash(
                            'notice',
                            'Hasło zostało zmienione'
                        );
                        return $this->redirectToRoute('admin');
                    }
                }else{
                    $this->addFlash(
                        'warning',
                        'Hasła nie są identyczne! Podaj nowe hasło po czym potwierdź jego poprawność'
                    );
                }
            }else{
                $this->addFlash(
                    'warning',
                    'Hasło które podałeś jako obecnie używane różni się od hasła faktycznie używanego!'
                );
            }
        }

        return $this->render('security/change_password.html.twig',[
            'username'          => $username,
            'form'              => $form->createView()
        ]);
    }

}
