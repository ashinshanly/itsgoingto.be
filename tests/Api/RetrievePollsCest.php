<?php

namespace App\Tests\Api;

use Codeception\Util\HttpCode;
use App\Tests\Api\BaseApiCest;

/**
 * API Tests for GET /api/polls
 */
class RetrievePollsCest extends BaseApiCest
{
    public function checkRouteTest(\ApiTester $I)
    {
        $user = $this->createUser($I, [
            'username' => 'admin',
            'password' => 'password123'
        ]);
        $token = $this->getTokenForUser($I, $user);
        $I->amBearerAuthenticated($token);

        $I->wantTo('Check call return 200 and matches json structure');
        $I->sendGET('/polls');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseMatchesJsonType([
            'count'    => 'integer',
            'total'    => 'integer',
            'entities' => 'array'
        ]);
    }

    public function returns401ForUnauthorizedUser(\ApiTester $I)
    {
        $I->wantTo('Check call returns 401');
        $I->sendGET('/polls');
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseIsJson();
    }

    public function returnsPollsTest(\ApiTester $I)
    {
        $user = $this->createUser($I, [
            'username' => 'admin',
            'password' => 'password123'
        ]);
        $token = $this->getTokenForUser($I, $user);
        $I->amBearerAuthenticated($token);

        $I->wantTo('Check returned polls match json structure');
        $I->sendGET('/polls');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseMatchesJsonType(
            [
                'id'             => 'integer',
                'identifier'     => 'string',
                'question'       => 'string',
                'multipleChoice' => 'boolean',
                'passphrase'     => 'string',
                'ended'          => 'boolean',
                'deleted'        => 'boolean',
                'responsesCount' => 'integer',
                'answers'        => 'array',
                'created'        => [
                    'date'          => 'string',
                    'timezone_type' => 'integer',
                    'timezone'      => 'string'
                ],
                'updated'        => [
                    'date'          => 'string',
                    'timezone_type' => 'integer',
                    'timezone'      => 'string'
                ]
            ],
            '$.entities[*]'
        );
        $I->seeResponseMatchesJsonType(
            [
                'id'   => 'integer',
                'type' => 'string:regex(/Answer/)'
            ],
            '$.entities[*].answers[*]'
        );
    }

    public function returnsPollsWithValuesTest(\ApiTester $I)
    {
        $user = $this->createUser($I, [
            'username' => 'admin',
            'password' => 'password123'
        ]);
        $token = $this->getTokenForUser($I, $user);
        $I->amBearerAuthenticated($token);

        $I->wantTo('Check returned polls match correct values');
        $I->sendGET('/polls');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'count' => 2,
            'total' => 2
        ]);
        $I->seeResponsePathContainsJson(
            [
                'id'             => $this->polls[0]->getId(),
                'identifier'     => 'he7gis',
                'question'       => 'Test Question 1',
                'multipleChoice' => false,
                'passphrase'     => '',
                'ended'          => false,
                'deleted'        => false,
                'responsesCount' => 2
            ],
            '$.entities[0]'
        );
        $I->seeResponsePathContainsJson(
            [
                'id'   => $this->polls[0]->getAnswers()[0]->getId(),
                'type' => 'Answer'
            ],
            '$.entities[0].answers[0]'
        );
        $I->seeResponsePathContainsJson(
            [
                'id'   => $this->polls[0]->getAnswers()[1]->getId(),
                'type' => 'Answer'
            ],
            '$.entities[0].answers[1]'
        );
    }

    public function returnsPaginatedPollsTest(\ApiTester $I)
    {
        for ($x = 0; $x < 50; $x++) {
            $this->polls[] = $this->createPoll($I, [
                'identifier'     => substr(chr(mt_rand(97, 122)) .substr(md5(time()), 1), 0, 6),
                'question'       => 'Test Question',
                'multipleChoice' => false,
                'passphrase'     => '',
                'deleted'        => false,
                'answers'        => [
                    'Answer 1',
                    'Answer 2'
                ]
            ]);
        }
        $user = $this->createUser($I, [
            'username' => 'admin',
            'password' => 'password123'
        ]);
        $token = $this->getTokenForUser($I, $user);
        $I->amBearerAuthenticated($token);

        $I->wantTo('Check first page of polls are returned');
        $I->sendGET('/polls');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'count' => 20,
            'total' => 52,
        ]);
        $I->seeResponsePathContainsJson(
            [
                'id'             => $this->polls[0]->getId(),
                'identifier'     => 'he7gis',
                'question'       => 'Test Question 1',
                'multipleChoice' => false,
                'passphrase'     => '',
                'ended'          => false,
                'deleted'        => false,
                'responsesCount' => 2
            ],
            '$.entities[0]'
        );

        $I->wantTo('Check second page of polls are returned');
        $I->sendGET('/polls', ['page' => 2]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'count' => 20,
            'total' => 52,
        ]);
        $I->seeResponsePathContainsJson(
            [
                'id'             => $this->polls[20]->getId(),
                'identifier'     => $this->polls[20]->getIdentifier(),
                'question'       => 'Test Question',
                'multipleChoice' => false,
                'passphrase'     => '',
                'ended'          => false,
                'deleted'        => false,
                'responsesCount' => 0
            ],
            '$.entities[0]'
        );

        $I->wantTo('Check page size affects returned');
        $I->sendGET('/polls', ['page' => 2, 'pageSize' => 25]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'count' => 25,
            'total' => 52,
        ]);
        $I->seeResponsePathContainsJson(
            [
                'id'             => $this->polls[25]->getId(),
                'identifier'     => $this->polls[25]->getIdentifier(),
                'question'       => 'Test Question',
                'multipleChoice' => false,
                'passphrase'     => '',
                'ended'          => false,
                'deleted'        => false,
                'responsesCount' => 0
            ],
            '$.entities[0]'
        );
    }

    public function returnsSortedPollsTest(\ApiTester $I)
    {
        $user = $this->createUser($I, [
            'username' => 'admin',
            'password' => 'password123'
        ]);
        $token = $this->getTokenForUser($I, $user);
        $I->amBearerAuthenticated($token);

        $I->wantTo('Check polls are returned with default sort of ID and asc');
        $I->sendGET('/polls');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'count' => 2,
            'total' => 2,
        ]);
        $I->seeResponsePathContainsJson(
            [
                'id'             => $this->polls[0]->getId(),
                'identifier'     => 'he7gis',
                'question'       => 'Test Question 1',
            ],
            '$.entities[0]'
        );

        $I->wantTo('Check polls are returned with default sort of ID and desc');
        $I->sendGET('/polls', ['sortDirection' => 'desc']);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'count' => 2,
            'total' => 2,
        ]);
        $I->seeResponsePathContainsJson(
            [
                'id'             => $this->polls[1]->getId(),
                'identifier'     => 'y3k0sn',
                'question'       => 'Test Question Deleted',
            ],
            '$.entities[0]'
        );

        $I->wantTo('Check polls are returned with sort = identifier and sortDirection = asc');
        $I->sendGET('/polls', ['sort' => 'identifier', 'sortDirection' => 'asc']);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'count' => 2,
            'total' => 2,
        ]);
        $I->seeResponsePathContainsJson(
            [
                'id'             => $this->polls[0]->getId(),
                'identifier'     => 'he7gis',
                'question'       => 'Test Question 1',
            ],
            '$.entities[0]'
        );

        $I->wantTo('Check polls are returned with sort = identifier and sortDirection = desc');
        $I->sendGET('/polls', ['sort' => 'identifier', 'sortDirection' => 'desc']);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'count' => 2,
            'total' => 2,
        ]);
        $I->seeResponsePathContainsJson(
            [
                'id'             => $this->polls[1]->getId(),
                'identifier'     => 'y3k0sn',
                'question'       => 'Test Question Deleted',
            ],
            '$.entities[0]'
        );

        $I->wantTo('Check polls are returned with sort = question and sortDirection = asc');
        $I->sendGET('/polls', ['sort' => 'question', 'sortDirection' => 'asc']);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'count' => 2,
            'total' => 2,
        ]);
        $I->seeResponsePathContainsJson(
            [
                'id'             => $this->polls[0]->getId(),
                'identifier'     => 'he7gis',
                'question'       => 'Test Question 1',
            ],
            '$.entities[0]'
        );

        $I->wantTo('Check polls are returned with sort = question and sortDirection = desc');
        $I->sendGET('/polls', ['sort' => 'question', 'sortDirection' => 'desc']);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'count' => 2,
            'total' => 2,
        ]);
        $I->seeResponsePathContainsJson(
            [
                'id'             => $this->polls[1]->getId(),
                'identifier'     => 'y3k0sn',
                'question'       => 'Test Question Deleted',
            ],
            '$.entities[0]'
        );

        $I->wantTo('Check polls are returned with sort = responsesCount and sortDirection = asc');
        $I->sendGET('/polls', ['sort' => 'responsesCount', 'sortDirection' => 'asc']);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'count' => 2,
            'total' => 2,
        ]);
        $I->seeResponsePathContainsJson(
            [
                'id'             => $this->polls[1]->getId(),
                'identifier'     => 'y3k0sn',
                'question'       => 'Test Question Deleted',
            ],
            '$.entities[0]'
        );

        $I->wantTo('Check polls are returned with sort = responsesCount and sortDirection = desc');
        $I->sendGET('/polls', ['sort' => 'responsesCount', 'sortDirection' => 'desc']);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'count' => 2,
            'total' => 2,
        ]);
        $I->seeResponsePathContainsJson(
            [
                'id'             => $this->polls[0]->getId(),
                'identifier'     => 'he7gis',
                'question'       => 'Test Question 1',
            ],
            '$.entities[0]'
        );
    }

    public function returns400ForInvalidSortOption(\ApiTester $I)
    {
        $user = $this->createUser($I, [
            'username' => 'admin',
            'password' => 'password123'
        ]);
        $token = $this->getTokenForUser($I, $user);
        $I->amBearerAuthenticated($token);

        $I->wantTo('Check call returns 400');
        $I->sendGET('/polls', ['sort' => 'something', 'sortDirection' => 'desc']);
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseIsJson();
    }
}
