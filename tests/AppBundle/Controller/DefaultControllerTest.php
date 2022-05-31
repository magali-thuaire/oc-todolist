<?php

namespace Tests\AppBundle\Controller;

use Tests\AppBundle\Utils\BaseWebTestCase;

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
