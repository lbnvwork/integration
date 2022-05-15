<?php

namespace Plus\Test\Suit\PaymentSystem\Processing\SepaViaGeDetails\Balance;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Balance\Output\CoreHandler as BalanceHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractCoreHandler;
use Plus\PaymentSystem\ProcessingFactory;
use Plus\Service\Processing\PaymentSystem as ProcessingService;
use Plus\Test\Suit\PaymentSystem\Processing\SepaViaGeDetails\AbstractTest;

class BalanceTest extends AbstractTest
{
    private const LOCAL_PATH = '/Balance/';

    /**
     * @dataProvider dataProvider
     *
     * @param array $psToPlusResponses
     * @param array $plusResponse
     *
     * @return void
     *
     */
    public function test(array $psToPlusResponses, array $plusResponse): void
    {
        $this->registerSequenceGenerator();
        $this->createHttpClientMock($psToPlusResponses);
        $gateRequest = $this->prepareGatePlusRequest(
            $this->getFixture(self::LOCAL_PATH . 'core_to_plus.json')
        );
        $response = (new ProcessingService(
            $gateRequest,
            $this->createGSMock(),
            new ProcessingFactory()
        ))->run();

        $this->assertSame(
            $plusResponse,
            $this->objectToArray($response)
        );
    }

    /**
     * @return array[]
     */
    public function dataProvider(): array
    {
        return [
            BalanceHandler::SUCCESS                  => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'success/ps_response.json',
                            200
                        )
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'success/plus_response.json')
            ],
            BalanceHandler::NOT_EQUAL_AVAILABLE      => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'not_equal_available/ps_response.json',
                            200
                        )
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'not_equal_available/plus_response.json'),
            ],
            BalanceHandler::CLIENT_EXCEPTION         => [
                [
                    [
                        'exception' =>
                            new ClientException(
                                'client error',
                                new GuzzleRequest('POST', ''),
                                $this->preparePsResponse(
                                    self::LOCAL_PATH . 'client_exception/ps_response.json',
                                    400
                                )
                            ),
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'client_exception/plus_response.json'),
            ],
            AbstractCoreHandler::MALFORMED     => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'malformed/ps_response.json',
                            200
                        )
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'malformed/plus_response.json'),
            ],
            AbstractCoreHandler::NETWORK_ERROR => [
                [
                    [
                        'exception' => new RequestException(
                            'network error',
                            new GuzzleRequest('POST', '')
                        ),
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'network_error/plus_response.json'),
            ],
        ];
    }
}
