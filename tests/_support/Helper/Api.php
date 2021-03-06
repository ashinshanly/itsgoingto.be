<?php

namespace Helper;

use Flow\JSONPath\JSONPath;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Api extends \Codeception\Module
{
    public function getEntityManager()
    {
        return $this->getModule('Doctrine2')->_getEntityManager();
    }

    public function getJWTService()
    {
        return $this->getModule('Symfony')
            ->grabServiceFromContainer('lexik_jwt_authentication.encoder');
    }

    public function seeResponsePathContainsJson($data = [], $path = '$')
    {
        $response = $this->getModule('REST')->grabResponse();
        $responsePart = (new JSONPath(json_decode($response, true)))->find($path);

        foreach ($data as $key => $value) {
            $this->assertArrayHasKey($key, $responsePart[0]);
            $this->assertEquals($value, $responsePart[0][$key]);
        }
    }
}
