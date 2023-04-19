<?php

namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\AnalyseService;

class AnalyseServiceTest extends KernelTestCase
{
    private $container;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->container = static::getContainer();
    }

    public function testSomething()
    {
        /** @var AnalyseService */
        $analyseService = $this->container->get(AnalyseService::class);

        $this->assertEquals('...', $analyseService->getMatrix());
    }
}
