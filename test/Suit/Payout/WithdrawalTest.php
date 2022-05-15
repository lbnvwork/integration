<?php

namespace Plus\Test\Suit\PaymentSystem\Processing\SepaViaGeDetails\Payout;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractCoreHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\Withdrawal\CoreHandler as WithdrawalHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\WithdrawalCheck\CheckStatusEvent as WithdrawalCheckStatusEvent;
use Plus\PaymentSystem\ProcessingFactory;
use Plus\Service\Processing\PaymentSystem as ProcessingService;
use Plus\Test\Suit\PaymentSystem\Processing\SepaViaGeDetails\AbstractTest;

class WithdrawalTest extends AbstractTest
{
    private const LOCAL_PATH = 'Payout/withdrawal/';

    /**
     * @dataProvider dataProvider
     *
     * @param array      $psToPlusResponses
     * @param array      $plusResponse
     * @param array|null $checkStatusEvent
     *
     * @return void
     */
    public function test(array $psToPlusResponses, array $plusResponse, array $checkStatusEvent = null): void
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
            WithdrawalHandler::FAILED => [
                [
                    $this->getDestinationsResponse(),
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'decline_0_failed/ps_response.json',
                            202
                        )
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'decline_0_failed/plus_response.json')
            ],
            WithdrawalHandler::ERROR_400_401 => [
                [
                    $this->getDestinationsResponse(),
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'decline_0_400/ps_response.json',
                            400
                        )
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'decline_0_400/plus_response.json'),
            ],
            WithdrawalHandler::ERROR_409_422 . ': 409' => [
                [
                    $this->getDestinationsResponse(),
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'error_409/ps_response.json',
                            409
                        )
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'error_409/plus_response.json'),
            ],
            WithdrawalHandler::ERROR_409_422 . ': 422' => [
                [
                    $this->getDestinationsResponse(),
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'error_422/ps_response.json',
                            422
                        )
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'error_422/plus_response.json'),
            ],
            WithdrawalHandler::INVALID_AMOUNT_OR_CURRENCY => [
                [
                    $this->getDestinationsResponse(),
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'invalid_amount_or_currency/ps_response.json',
                            202
                        )
                    ],
                ],
                $this->prepareArrayFromJson(
                    self::LOCAL_PATH . 'invalid_amount_or_currency/plus_response.json'
                )
            ],
            WithdrawalHandler::SUCCESS => [
                [
                    $this->getDestinationsResponse(),
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'success_0/ps_response.json',
                            202
                        )
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'success_0/plus_response.json')
            ],
            WithdrawalHandler::PENDING => [
                [
                    $this->getDestinationsResponse(),
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'pending/ps_response.json',
                            202
                        )
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'pending/plus_response.json'),
                [
                    'isAddCheckStatusEvent'    => true,
                    'isCancelCheckStatusEvent' => false,
                    WithdrawalCheckStatusEvent::class
                ]
            ],
            AbstractCoreHandler::NETWORK_ERROR => [
                [
                    $this->getDestinationsResponse(),
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
            AbstractCoreHandler::MALFORMED => [
                [
                    $this->getDestinationsResponse(),
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'malformed/ps_response.json',
                            202
                        )
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'malformed/plus_response.json'),
            ],
            AbstractCoreHandler::MALFORMED . ': with_id' => [
                [
                    $this->getDestinationsResponse(),
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'malformed_with_id/ps_response.json',
                            202
                        )
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'malformed_with_id/plus_response.json'),
                [
                    'isAddCheckStatusEvent'    => true,
                    'isCancelCheckStatusEvent' => false,
                    WithdrawalCheckStatusEvent::class
                ]
            ],
            AbstractCoreHandler::MALFORMED . ': without_id' => [
                [
                    $this->getDestinationsResponse(),
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'malformed_without_id/ps_response.json',
                            202
                        )
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'malformed_without_id/plus_response.json'),
            ],
            'InvalidHttpResponseCode' => [
                [
                    $this->getDestinationsResponse(),
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'invalidHttpResponseCode/ps_response.json',
                            405
                        ),
                    ]
                ],
                $this->prepareArrayFromJson(
                    self::LOCAL_PATH . 'invalidHttpResponseCode/plus_response.json'
                ),
            ],
        ];
    }

    /**
     * @return GuzzleResponse[]
     */
    private function getDestinationsResponse(): array
    {
        return [
            'ps_response' => $this->preparePsResponse(
                self::LOCAL_PATH . 'ps_response_destinations.json',
                201
            )
        ];
    }
}
