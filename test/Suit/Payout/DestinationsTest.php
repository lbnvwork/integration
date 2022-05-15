<?php

namespace Plus\Test\Suit\PaymentSystem\Processing\SepaViaGeDetails\Payout;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractCoreHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Destinations\CoreHandler as DestinationsHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\DestinationsCheck\CheckStatusEvent;
use Plus\PaymentSystem\ProcessingFactory;
use Plus\Service\Processing\PaymentSystem as ProcessingService;
use Plus\Test\Suit\PaymentSystem\Processing\SepaViaGeDetails\AbstractTest;

class DestinationsTest extends AbstractTest
{
    private const LOCAL_PATH = '/Payout/destinations/';

    /**
     * @dataProvider dataProvider
     *
     * @return void
     *
     * @throws \Exception
     */
    public function test(array $psToPlusResponses, array $plusResponse, array $checkStatusEvent = null)
    {
        $this->registerSequenceGenerator();
        $this->createHttpClientMock($psToPlusResponses);
        if ($checkStatusEvent) {
            $this->registerEventQueueMock(...array_values($checkStatusEvent));
        }
        $gateRequest = $this->prepareGatePlusRequest($this->getFixture('Payout/core_to_plus.json'));

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
     * @return array
     */
    public function dataProvider(): array
    {
        return [
            DestinationsHandler::AUTHORIZED => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'authorized/ps_response.json',
                            201
                        )
                    ],
                    $this->getWithdrawalPsResponse()
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'success_plus_response.json')
            ],
            'ClientExceptionError' => [
                [
                    [
                        'exception' =>
                            new ClientException(
                                'client error',
                                new GuzzleRequest('POST', ''),
                                $this->preparePsResponse(
                                    self::LOCAL_PATH . 'error_400_401/ps_response.json',
                                    400
                                )
                            ),
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'error_400_401/plus_response.json'),
            ],
            DestinationsHandler::ERROR_400_401 => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'error_400_401/ps_response.json',
                            401
                        ),
                    ]
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'error_400_401/plus_response.json'),
            ],
            DestinationsHandler::ERROR_409_422 . ':409' => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'error_409/ps_response.json',
                            409
                        ),
                    ]
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'error_409/plus_response.json'),
            ],
            DestinationsHandler::ERROR_409_422 . ':422' => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'error_422/ps_response.json',
                            422
                        ),
                    ]
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'error_422/plus_response.json'),
            ],
            'InvalidHttpResponseCode' => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'invalidHttpResponseCode/ps_response.json',
                            405
                        ),
                    ]
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'invalidHttpResponseCode/plus_response.json'),
            ],
            AbstractCoreHandler::MALFORMED => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'malformed/ps_response.json',
                            201
                        ),
                    ]
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'malformed/plus_response.json'),
            ],
            AbstractCoreHandler::NETWORK_ERROR => [
                [
                    [
                        'exception' => new RequestException(
                            'network error',
                            new GuzzleRequest(
                                'POST',
                                ''
                            )
                        ),
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'network_error/plus_response.json'),
            ],
            DestinationsHandler::UNAUTHORIZED => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'unauthorized/ps_response.json',
                            201
                        )
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'unauthorized/plus_response.json'),
                ['isAddCheckStatusEvent' => true, 'isCancelCheckStatusEvent' => false, CheckStatusEvent::class],
            ],
            'parseJsonException' => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'json_exception/ps_response.json',
                            201
                        ),
                    ]
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'json_exception/plus_response.json'),
            ],
            AbstractCoreHandler::MALFORMED . ':invalid_status' => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'malformed_invalid_status/ps_response.json',
                            201
                        ),
                    ]
                ],
                $this->prepareArrayFromJson(
                    self::LOCAL_PATH . 'malformed_invalid_status/plus_response.json'
                ),
            ],
        ];
    }

    /**
     * @return GuzzleResponse[]
     */
    private function getWithdrawalPsResponse(): array
    {
        return [
            'ps_response' => $this->preparePsResponse(
                'Payout/withdrawal/success_0/ps_response.json',
                202
            )
        ];
    }
}
