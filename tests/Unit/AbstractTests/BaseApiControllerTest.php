<?php

namespace App\Tests\Unit\AbstractTests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Prophecy\ObjectProphecy;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use App\Interfaces\ApiControllerInterface;
use App\Controller\Api\PollApiController;
use App\Tests\Unit\AbstractTests\BaseTest;
use App\Entity\Poll;
use App\Entity\Answer;
use App\Entity\UserResponse;
use App\Entity\User;
use App\Service\IdentifierService;
use App\Service\PollEndService;

abstract class BaseApiControllerTest extends BaseTest
{
    /**
     * Name of the class being tested.
     *
     * @var string
     */
    protected $controllerClass;

    /**
     * An instance of the controller being tested.
     *
     * @var ApiControllerInterface
     */
    protected $controller;

    /**
     * An api url.
     *
     * @var string
     */
    protected $apiUrl;

    /**
     * @var ObjectProphecy
     */
    protected $answer;

    /**
     * @var ObjectProphecy
     */
    protected $poll;

    /**
     * @var ObjectProphecy
     */
    protected $user;

    /**
     * @var ObjectProphecy
     */
    protected $answerRepository;

    /**
     * @var ObjectProphecy
     */
    protected $userResponseRepo;

    /**
     * @var ObjectProphecy
     */
    protected $userRepo;

    /**
     * @var ObjectProphecy
     */
    protected $pollRepository;

    /**
     * @var ObjectProphecy
     */
    protected $queryBuilder;

    /**
     * @var ObjectProphecy
     */
    protected $query;

    /**
     * @var ObjectProphecy
     */
    protected $authorizationChecker;

    /**
     * @var ObjectProphecy
     */
    protected $identifierService;

    /**
     * @var ObjectProphecy
     */
    protected $pollEndService;

    /**
     * Test setup.
     *
     * @throws \Exception if $this->controllerClass is not set.
     * @throws \Exception if $this->ControllerClass is not the name of a class implementing the ApiControllerInterface
     */
    public function setUp()
    {
        parent::setUp();

        if (!$this->controllerClass) {
            $message = 'Classes extending '
                       . BaseApiControllerTest::class
                       . ' must declare a value for $controllerClass.';
            throw new \Exception($message);
        }

        $this->answer = $this->prophesize(Answer::class);
        $this->answer->getId()->willReturn(5);
        $this->answer->getResponses()->willReturn([new UserResponse()]);
        $this->answer->addResponse(Argument::any())->willReturn(null);
        $this->answer->extract()->willReturn([
            'id'             => 5,
            'answer'         => 'Answer A',
            'poll'           => [
                'type' => 'poll',
                'id'   => 2
            ],
            'responsesCount' => 1,
        ]);

        $this->poll = $this->prophesize(Poll::class);
        $this->poll->getId()->willReturn(2);
        $this->poll->getIdentifier()->willReturn('lkjas79h');
        $this->poll->isMultipleChoice()->willReturn(false);
        $this->poll->isEnded()->willReturn(false);
        $this->poll->getPassphrase()->willReturn('');
        $this->poll->hasPassphrase()->willReturn(false);
        $this->poll->extract()->willReturn([
            'id'             => 2,
            'identifier'     => 'lkjas79h',
            'question'       => 'Question text?',
            'answers'        => [],
            'responsesCount' => 2,
            'multipleChoice' => false,
            'deleted'        => false,
        ]);
        $this->poll->getResponses()->willReturn([new UserResponse(), new UserResponse()]);
        $this->poll->addResponse(Argument::any())->willReturn(null);
        $this->poll->getAnswers()->willReturn([$this->answer->reveal(), $this->answer->reveal()]);
        $this->poll->setDeleted(Argument::any())->willReturn($this->poll->reveal());

        $this->user = $this->prophesize(User::class);
        $this->user->getUsername()->willReturn('user');

        $this->answerRepository = $this->prophesize(EntityRepository::class);
        $this->answerRepository->findOneBy(Argument::any())->willReturn($this->answer->reveal());

        $this->userResponseRepo = $this->prophesize(EntityRepository::class);
        $this->userResponseRepo->findOneBy(Argument::any())->willReturn(null);
        $this->userResponseRepo->findBy(Argument::any())->willReturn(null);

        $this->userRepo = $this->prophesize(EntityRepository::class);
        $this->userRepo->findOneBy(Argument::any())->willReturn($this->user->reveal());

        $this->pollRepository = $this->prophesize(EntityRepository::class);
        $this->pollRepository->findOneBy(Argument::any())->willReturn($this->poll->reveal());

        $this->queryBuilder = $this->prophesize(QueryBuilder::class);
        $this->queryBuilder->where(Argument::any())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->andWhere(Argument::any())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->select(Argument::any())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->setFirstResult(Argument::any())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->setMaxResults(Argument::any())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->leftJoin(Argument::any(), Argument::any())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->groupBy(Argument::any())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->orderBy(Argument::any(), Argument::any())->willReturn($this->queryBuilder->reveal());

        $this->query = $this->prophesize(AbstractQuery::class);
        $this->query->getResult()->willReturn([$this->poll->reveal()]);
        $this->queryBuilder->getQuery(Argument::any())->willReturn($this->query->reveal());
        $this->pollRepository->createQueryBuilder(Argument::any())->willReturn($this->queryBuilder->reveal());
        $this->userResponseRepo->createQueryBuilder(Argument::any())->willReturn($this->queryBuilder->reveal());

        $this->entityManager = $this->prophesize(EntityManager::class);
        $this->entityManager->getRepository(Answer::class)
            ->willReturn($this->answerRepository->reveal());
        $this->entityManager->getRepository(Poll::class)
            ->willReturn($this->pollRepository->reveal());
        $this->entityManager->getRepository(UserResponse::class)
            ->willReturn($this->userResponseRepo->reveal());
        $this->entityManager->getRepository(User::class)
            ->willReturn($this->userRepo->reveal());
        $this->entityManager->persist(Argument::any())
            ->willReturn(true);
        $this->entityManager->remove(Argument::any())
            ->willReturn(true);
        $this->entityManager->flush(Argument::any())
            ->willReturn(true);

        $this->authorizationChecker = $this->prophesize(AuthorizationCheckerInterface::class);
        $this->authorizationChecker->isGranted('ROLE_ADMIN')->willReturn(false);

        $this->identifierService = $this->prophesize(IdentifierService::class);
        $this->identifierService->getCustomUserID(Argument::any())->willReturn('9873fdanba8qge9dfsaq39');

        $this->pollEndService = $this->prophesize(PollEndService::class);
        $this->pollEndService->updateIfEnded(Argument::any())->will(function ($args) {
            return $args[0];
        });

        $this->controller = new $this->controllerClass(
            $this->entityManager->reveal(),
            $this->authorizationChecker->reveal(),
            $this->identifierService->reveal(),
            $this->pollEndService->reveal(),
            ''
        );
        if (!$this->controller instanceof ApiControllerInterface) {
            $message = '$controllerClass must represent a class implementing the '
                       . ApiControllerInterface::class
                       . ' interface.';
            throw new \Exception($message);
        }
    }

    /**
     * Test tear down.
     */
    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Test that if an OPTIONS request is made, a Response is returned.
     */
    public function testAPIOptionsRequestReturnsResponse()
    {
        $request = Request::create($this->apiUrl, Request::METHOD_OPTIONS);

        $response = $this->controller->apiAction($request, 0);

        $this->assertEquals(
            new Response(),
            $response
        );
    }

    /**
     * Test that if a request with an unsupported method is made, a HTTP Exception is thrown.
     */
    public function testApiHeadRequestReturnsHttpException()
    {
        $request = Request::create($this->apiUrl, Request::METHOD_HEAD);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Method not allowed.');

        $this->controller->apiAction($request, 0);
    }
}
