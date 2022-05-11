<?php

namespace App\Service;

use Knp\Bundle\MarkdownBundle\MarkdownParserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Security\Core\Security;

class MarkdownHelper
{
    private $markdown;
    private $logger;
    private $isDebug;
    private $security;
    private $cache;

    public function __construct(CacheInterface $cache, MarkdownParserInterface $markdown, LoggerInterface $markdownLogger, bool $isDebug, Security $security)
    {
        $this->markdown = $markdown;
        $this->logger = $markdownLogger;
        $this->isDebug = $isDebug;
        $this->security = $security;
        $this->cache = $cache;
    }

    public function parse(string $source): string
    {
        if (stripos($source, 'bacon') !== false) {
            $this->logger->info('They are talking about bacon again!', [
                'user' => $this->security->getUser()
            ]);
        }

        // skip caching entirely in debug
        if ($this->isDebug) {
            return $this->markdown->transformMarkdown($source);
        }

        $item = $this->cache->getItem('markdown_'.md5($source));
        if (!$item->isHit()) {
            $item->set($this->markdown->convert($source));
            $this->cache->save($item);
        }

        return $item->get();
    }
}
