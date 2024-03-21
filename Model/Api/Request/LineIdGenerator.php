<?php

namespace Dintero\Checkout\Model\Api\Request;

use Dintero\Checkout\Helper\Config;

class LineIdGenerator
{
    /**
     * @var Config $configHelper
     */
    private $configHelper;

    /**
     * @var GeneratorInterface[] $generators
     */
    private $generators = [];

    /**
     * @var GeneratorInterface $generator
     */
    private $generator;

    /**
     * Define class dependencies
     *
     * @param Config $configHelper
     * @param GeneratorInterface[] $generators
     */
    public function __construct(Config $configHelper, $generators)
    {
        $this->configHelper = $configHelper;
        $this->generators = $generators;
    }

    /**
     * Retrieve generator
     *
     * @param string $code
     * @return GeneratorInterface
     * @throws \Exception
     */
    private function getGenerator($code)
    {
        if ($this->generator) {
            return $this->generator;
        }

        $generator = $this->generators[$code ?? 'sku'] ?? null;
        if (!$generator || !($generator instanceof GeneratorInterface)) {
            throw new \Exception(__('Line id generator is not valid.'));
        }

        $this->generator = $generator;
        return $this->generator;
    }

    /**
     * Generate line id
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return string
     * @throws \Exception
     */
    public function generate(\Magento\Quote\Model\Quote\Item $item)
    {
        return $this->getGenerator($item->getQuote()->getDinteroGeneratorCode())->execute($item);
    }
}
