<?php

namespace WCChatGPT;

defined( 'ABSPATH' ) or die;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;

class GPTDescriptionGenerator
{
    private $client;

    private $apiKey;

    private $concurrentRequests;

    private $descQuery;

    private $metaTitlteQuery;

    private $metaDescQuery;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = 
            get_option( '_openai_key' );
        $this->concurrentRequests = 
            get_option( '_concurrent_requests' );
        $this->descQuery = 
            get_option( '_query_desc' );
        $this->metaTitlteQuery = 
            get_option( '_query_meta_title' );
        $this->metaDescQuery = 
            get_option( '_query_meta_desc' );
    }

    public function generate( int $productId, array $fields ): array
    {
        $desc = [];
        foreach ( $fields as $field ) {
            $response = ( $this->client )->send( 
                $this->getRequest( $productId, $field ) 
            );
            $content = json_decode( $response->getBody()->getContents(), true );
            $desc[ $field ] = $content[ 'choices' ][0][ 'message' ][ 'content' ];
        }

        return $desc;
    }

    public function generateForAll( array $products, callable $success, callable $fail ): void
    {
        $requests = function ( array $products ) {
            foreach ( $products as $product ) {
                foreach ( $product[ 'fields' ] as $field ) {
                    yield $product[ 'id' ] . '.' . $field => 
                        $this->getRequest( $product[ 'id' ], $field );
                }
            }
        };

        $pool = new Pool( $this->client, $requests( $products ), [
            'concurrency' => $this->concurrentRequests,
            'fulfilled' => function ( Response $res, $product ) use( $success ) {
                $body = json_decode( ( string ) $res->getBody(), true );
                $text = $body[ 'choices' ][0][ 'message' ][ 'content' ];

                $productId = explode( '.', $product )[0];
                $field = explode( '.', $product )[1];
                
                $success( $productId, $field, $text );
            },
            'rejected' => function ( RequestException $e, $product ) use( $fail ) {
                $productId = explode( '.', $product )[0];
                $field = explode( '.', $product )[1];
                
                $fail( $e, $productId, $field );
            },
        ] );

        ( $pool->promise() )->wait();
    }

    private function getRequest( int $productId, string $field ): Request
    {
        $headers = [
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ];

        switch ( $field ) {
            case 'desc':
                $query = preg_replace_callback( 
                    '/{title}/', 
                    fn() => get_the_title( $productId ),
                    $this->descQuery 
                );
            break;
            case 'meta_title':
                $query = preg_replace_callback( 
                    '/{title}/', 
                    fn() => get_the_title( $productId ),
                    $this->metaTitlteQuery 
                );
            break;
            case 'meta_desc':
                $query = preg_replace_callback( 
                    '/{title}/', 
                    fn() => get_the_title( $productId ),
                    $this->metaDescQuery 
                );
            break;
            default:
                throw new Exception( "Invalid field parameter: {$field}" );
        }
        
        $body = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [ 
                [
                    'role' => 'user',
                    'content' => $query,
                ]
            ],
        ];

        $request = new Request(
            'POST',
            'https://api.openai.com/v1/chat/completions',
            $headers,
            json_encode( $body )
        );

        return $request;
    }
}
