<?php

namespace NeosRulez\DirectMail\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Mvc\ActionRequest;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Mvc\Routing\Exception\MissingActionNameException;
use Neos\Flow\Http\Exception as HttpException;
use GuzzleHttp\Psr7\ServerRequest;
use Neos\Flow\Cli\CommandRequestHandler;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Utility\MediaTypes;

/**
 *
 * @Flow\Scope("singleton")
 */
class NodeService
{

    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @Flow\InjectConfiguration(package="Neos.ContentRepository", path="contentDimensions")
     * @var array
     */
    protected $contentDimensions;

    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * @var ActionRequest
     */
    protected $actionRequestForUriBuilder;

    /**
     * @var Bootstrap
     * @Flow\Inject
     */
    protected $bootstrap;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @param string $nodeUri
     * @param array $recipient
     * @return false|array{nodeUri: string, subject: string, replyTo: string|false, senderName: string|false, attachments: array<int, array{mediaType: string, fileExtensions: string, temporaryLocalCopyFilename: string, mailFilename: string}>}
     */
    public function nodeUri(string $nodeUri, array $recipient)
    {
        $recipientDimensions = $recipient['dimensions'] !== null ? $recipient['dimensions'] : [];
        $dimensions = $this->getDimensions($recipientDimensions);
        $context = $this->contextFactory->create([
            'workspaceName' => 'live',
            'dimensions' => $dimensions
        ]);

        $node = $context->getNodeByIdentifier($nodeUri);

        if ($node === null) {
            return false;
        }

        if (!$this->hasRecipientDimension($node->getDimensions(), $recipientDimensions)) {
            return false;
        }

        $attachments = $this->getAttachments($node);

        $replyTo = false;
        if ($node->hasProperty('replyTo')) {
            $replyTo = $node->getProperty('replyTo');
        }

        $senderName = false;
        if ($node->hasProperty('senderName')) {
            $senderName = $node->getProperty('senderName');
        }

        return [
            'nodeUri' => $this->buildUriPathForNode($node),
            'subject' => $node->getProperty('title'),
            'replyTo' => $replyTo,
            'senderName' => $senderName,
            'attachments' => $attachments,
        ];
    }

    /**
     * Get attachments of a node
     * @param NodeInterface $node
     * @return Array<int, array{mediaType: string, fileExtensions: string, temporaryLocalCopyFilename: string, mailFilename: string}>
     */
    private function getAttachments(NodeInterface $node): array
    {
        if (!$node->hasProperty('attachments')) {
            return [];
        }

        $attachmentAssets = $node->getProperty('attachments');
        if (empty($attachmentAssets)) {
            return [];
        }

        $attachments = [];
        foreach ($attachmentAssets as $attachmentAsset) {
            $sourceMediaType = MediaTypes::parseMediaType($attachmentAsset->getMediaType());
            $assetFileName = $attachmentAsset->getResource()->getFileName();
            $fileExtension = $attachmentAsset->getResource()->getFileExtension();
            $temporaryLocalCopyFilename = $attachmentAsset->getResource()->createTemporaryLocalCopy();
            $mailFileName = str_replace(':', '', str_replace(' ', '_', $assetFileName));

            $attachments[] = [
                'mediaType' => $sourceMediaType['type'] . '/' . $sourceMediaType['subtype'],
                'fileExtensions' => $fileExtension,
                'temporaryLocalCopyFilename' => $temporaryLocalCopyFilename,
                'mailFilename' => $mailFileName
            ];
        }

        return $attachments;
    }

    /**
     * @param array $nodeDimensions
     * @param array $recipientDimensions
     * @return bool
     */
    private function hasRecipientDimension(array $nodeDimensions, array $recipientDimensions): bool
    {
        if (empty($recipientDimensions)) {
            return true;
        }

        foreach ($nodeDimensions as $nodeDimensionIterator => $nodeDimension) {
            foreach ($nodeDimension as $nodeDimensionValue) {
                if ($recipientDimensions[$nodeDimensionIterator] === $nodeDimensionValue) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array $recipientDimensions
     * @return array
     */
    private function getDimensions(array $recipientDimensions): array
    {
        $result = [];
        if (!empty($this->contentDimensions)) {
            foreach ($this->contentDimensions as $contentDimensionIterator => $contentDimension) {
                if (array_key_exists($contentDimensionIterator, $recipientDimensions)) {
                    foreach ($contentDimension['presets'] as $contentDimensionPresetIterator => $contentDimensionPreset) {
                        if ($recipientDimensions[$contentDimensionIterator] == $contentDimensionPresetIterator) {
                            if (array_key_exists('values', $contentDimensionPreset)) {
                                $result[$contentDimensionIterator] = $contentDimensionPreset['values'];
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Creates a (relative) URI for the given $nodeContextPath removing the "@workspace-name" from the result
     *
     * @param NodeInterface $node
     * @return string the resulting (relative) URI
     * @throws MissingActionNameException
     * @throws HttpException
     */
    protected function buildUriPathForNode(NodeInterface $node): string
    {
        return $this->getUriBuilder()
            ->uriFor('show', ['node' => $node], 'Frontend\\Node', 'Neos.Neos');
    }

    /**
     * Creates an UriBuilder instance for the current request
     *
     * @return UriBuilder
     */
    protected function getUriBuilder(): UriBuilder
    {
        if ($this->uriBuilder !== null) {
            return $this->uriBuilder;
        }

        $this->uriBuilder = new UriBuilder();
        $this->uriBuilder
            ->setFormat('html')
            ->setCreateAbsoluteUri(false)
            ->setRequest($this->getActionRequestForUriBuilder());

        return $this->uriBuilder;
    }

    /**
     * Returns the current http request or a generated http request
     * based on a configured baseUri to allow redirect generation
     * for CLI requests.
     *
     * @return ActionRequest
     */
    protected function getActionRequestForUriBuilder(): ?ActionRequest
    {
        if ($this->actionRequestForUriBuilder) {
            return $this->actionRequestForUriBuilder;
        }

        /** @var HttpRequestHandlerInterface $requestHandler */
        $requestHandler = $this->bootstrap->getActiveRequestHandler();

        if ($requestHandler instanceof CommandRequestHandler) {
            // Generate a custom request when the current request was triggered from CLI
            $baseUri = $this->settings['baseUri'] ?? 'http://localhost';

            // Prevent `index.php` appearing in generated redirects
            putenv('FLOW_REWRITEURLS=1');

            $httpRequest = new ServerRequest('POST', $baseUri);
        } else {
            $httpRequest = $requestHandler->getHttpRequest();
        }

        $routeParameters = $httpRequest->getAttribute('routingParameters') ?? RouteParameters::createEmpty();
        $httpRequest = $httpRequest->withAttribute('routingParameters', $routeParameters->withParameter('requestUriHost', $httpRequest->getUri()->getHost()));
        // From Flow 6+ we have to use a static method to create an ActionRequest. Earlier versions use the constructor.
        $this->actionRequestForUriBuilder = ActionRequest::fromHttpRequest($httpRequest);

        return $this->actionRequestForUriBuilder;
    }
}
