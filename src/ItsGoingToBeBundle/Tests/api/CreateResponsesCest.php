<?php

namespace ItsGoingToBeBundle\Tests\api;

use Codeception\Util\HttpCode;
use ItsGoingToBeBundle\Tests\api\BaseApiCest;
use ItsGoingToBeBundle\ApiTester;

/**
 * API Tests for POST /api/polls/:identifier/responses
 */
class CreateResponsesCest extends BaseApiCest
{
  public function checkRouteTest(ApiTester $I)
  {
    $I->wantTo('Check call return 400');
    $I->sendPOST('/polls/he7gis/responses');
    $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    $I->seeResponseIsJson();
  }

  public function returns404Test(ApiTester $I)
  {
    $I->wantTo('Check call return 404');
    $I->sendPOST('/polls/y3k0sn/responses');
    $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    $I->seeResponseIsJson();
  }

  public function returnsErrorMessagesAnd400Test(ApiTester $I)
  {
    $I->wantTo('Check call returns errors');
    $I->sendPOST('/polls/he7gis/responses');
    $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    $I->seeResponseIsJson();
    $I->seeResponseMatchesJsonType([
      'errors' => 'array',
    ]);
    $I->seeResponseContainsJson([
      'errors' => [
        'No answers have been provided'
      ]
    ]);
  }

  public function returnsResponseAndPersistsResponseTest(ApiTester $I)
  {
    $I->wantTo('Check call returns responses and persists responses');
    $I->sendPOST('/polls/he7gis/responses', [
      'answers' => [
        $this->polls[0]->getAnswers()[1]->getId()
      ]
    ]);
    $I->seeResponseCodeIs(HttpCode::OK);
    $I->seeResponseIsJson();
    $I->seeResponseMatchesJsonType([
      'responsesCount' => 'integer',
      'answers'        => 'array',
      'userResponses'  => 'array'
    ]);
    $I->seeResponseMatchesJsonType([
      'id'             => 'integer',
      'responsesCount' => 'integer'
    ],
    '$.answers[*]');
    $I->seeResponseContainsJson([
      'responsesCount' => 3,
      'userResponses'  => [$this->polls[0]->getAnswers()[1]->getId()],
    ]);
    $I->seeResponsePathContainsJson([
      'id'             => $this->polls[0]->getAnswers()[0]->getId(),
      'responsesCount' => 2
    ],
    '$.answers[0]');
    $I->seeResponsePathContainsJson([
      'id'             => $this->polls[0]->getAnswers()[1]->getId(),
      'responsesCount' => 1
    ],
    '$.answers[1]');
  }
}

// TODO : Test multiple choice
