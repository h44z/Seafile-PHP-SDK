<?php

namespace Seafile\Resource;

use \Seafile\Type\Library as LibraryType;

/**
 * Handles everything regarding Seafile libraries.
 *
 * PHP version 5
 *
 * @category  API
 * @package   Seafile\Resource
 * @author    Rene Schmidt DevOps UG (haftungsbeschränkt) & Co. KG <rene@reneschmidt.de>
 * @copyright 2015 Rene Schmidt DevOps UG (haftungsbeschränkt) & Co. KG <rene@reneschmidt.de>
 * @license   https://opensource.org/licenses/MIT MIT
 * @link      https://github.com/rene-s/seafile-php-sdk
 */
class Library extends AbstractResource
{
    /**
     * List libraries
     * @return LibraryType[]
     */
    public function getAll()
    {
        $response = $this->client->request('GET', $this->client->getConfig('base_uri') . '/repos/');

        $json = json_decode($response->getBody());

        $libCollection = [];

        foreach ($json as $lib) {
            $libCollection[] = (new LibraryType)->fromJson($lib);
        }

        return $libCollection;
    }

    /**
     * Get library info
     * @param string $libraryId Library ID
     * @return LibraryType
     */
    public function getById($libraryId)
    {
        $response = $this->client->request(
            'GET',
            $this->client->getConfig('base_uri') . '/repos/' . $libraryId . '/'
        );

        $json = json_decode($response->getBody());

        return (new LibraryType)->fromJson($json);
    }

    /**
     * Decrypt library
     * @param String $libraryId Library ID
     * @param array  $options   Options
     * @return Bool Decryption succes
     * @throws \Exception
     */
    public function decrypt($libraryId, array $options)
    {
        $hasQueryParams = array_key_exists('query', $options);
        $hasPassword = $hasQueryParams && array_key_exists('password', $options['query']);

        if (!$hasQueryParams || !$hasPassword) {
            throw new \Exception('Password query parameter is required to decrypt library');
        }

        $response = $this->client->request(
            'POST',
            $this->client->getConfig('base_uri') . '/repos/' . $libraryId . '/',
            $options
        );

        return json_decode($response->getBody()) === 'success';
    }

    /**
     * Check if $name exists
     *
     * @param String $name Library name
     * @return bool
     */
    public function exists($name)
    {
        $libraries = $this->getAll();

        foreach ($libraries as $lib) {
            if ($lib->name === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a new library
     *
     * @param String $name        Library name
     * @param string $description Library description
     * @param mixed  $password    false means no encryption, any other string is used as password
     * @return bool
     */
    public function create($name, $description = "new repo", $password = false)
    {
        // only create a library which is not empty to prevent wrong implementation
        if (empty($name)) {
            return false;
        }

        // Do not create libraries that already exist
        if ($this->exists($name)) {
            return false;
        }

        $uri = sprintf(
            '%s/repos/',
            $this->clipUri($this->client->getConfig('base_uri'))
        );

        $multipartData = [
            [
                'name' => 'name',
                'contents' => $name,
            ],
            [
                'name' => 'desc',
                'contents' => $description,
            ],
        ];

        if ($password !== false) {
            $multipartData[] = [
                'name' => 'passwd',
                'contents' => $password,
            ];
        }

        $response = $this->client->request(
            'POST',
            $uri,
            [
                'headers' => ['Accept' => 'application/json'],
                'multipart' => $multipartData,
            ]
        );

        return $response->getStatusCode() === 200;
    }

    /**
     * Remove a library
     *
     * @param String $libraryId Library ID
     * @return bool
     */
    public function remove($libraryId)
    {
        // do not allow empty id's
        if (empty($libraryId)) {
            return false;
        }

        $uri = sprintf(
            '%s/repos/%s/',
            $this->clipUri($this->client->getConfig('base_uri')),
            $libraryId
        );

        $response = $this->client->request(
            'DELETE',
            $uri,
            [
                'headers' => ['Accept' => 'application/json'],
            ]
        );

        return $response->getStatusCode() === 200;
    }
}
