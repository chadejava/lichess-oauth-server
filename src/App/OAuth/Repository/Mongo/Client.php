<?php

namespace App\OAuth\Repository\Mongo;

use App\OAuth\ClientCreatorInterface;
use App\OAuth\Entity\Client as ClientEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use MongoDB\Collection;

final class Client implements ClientRepositoryInterface, ClientCreatorInterface
{
    /**
     * @var Collection
     */
    private $collection;

    /**
     * Initializes a new instance of this class.
     *
     * @param Collection $collection
     */
    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Get a client.
     *
     * @param string $clientIdentifier The client's identifier
     * @param string $grantType The grant type used
     * @param null|string $clientSecret The client's secret (if sent)
     * @param bool $mustValidateSecret If true the client must attempt to validate the secret if the client
     *                                        is confidential
     *
     * @return ClientEntityInterface
     * @throws \MongoDB\Exception\UnsupportedException
     * @throws \MongoDB\Exception\InvalidArgumentException
     * @throws \MongoDB\Driver\Exception\RuntimeException
     */
    public function getClientEntity($clientIdentifier, $grantType = null, $clientSecret = null, $mustValidateSecret = true)
    {
        $data = $this->collection->findOne([
            'client_id' => $clientIdentifier,
        ]);

        if (!$data) {
            return null;
        }

        if ($mustValidateSecret && $data['client_secret'] !== $clientSecret) {
            return null;
        }

        $client = new ClientEntity(
            $data['client_id'],
            $data['client_secret'],
            $data['name'],
            $data['redirect_uri']->getArrayCopy()
        );

        return $client;
    }

    /**
     * Creates a new client.
     *
     * @param ClientEntity $client
     * @return void
     * @throws \MongoDB\Exception\InvalidArgumentException
     * @throws \MongoDB\Driver\Exception\RuntimeException
     */
    public function createClient(ClientEntity $client)
    {
        $data = [
            'client_id' => $client->getIdentifier(),
            'client_secret' => $client->getClientSecret(),
            'name' => $client->getName(),
            'redirect_uri' => $client->getRedirectUri()
        ];

        $this->collection->insertOne($data);
    }
}
