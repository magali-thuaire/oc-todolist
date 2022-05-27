<?php

namespace App\Tests\Controller;

use App\Tests\Utils\BaseWebTestCase;

class DefaultControllerTest extends BaseWebTestCase
{
    /**
     * @dataProvider getUnauthorizedActions
     */
    public function testUnauthorizedAction($uri)
    {
        $this->unauthorizedAction($uri);
    }

    public function getUnauthorizedActions()
    {
        return [
            ['/'],
        ];
    }
}
