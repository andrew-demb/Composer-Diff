<?php

/**
 * JBZoo Toolbox - Composer-Diff
 *
 * This file is part of the JBZoo Toolbox project.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Composer-Diff
 * @license    MIT
 * @copyright  Copyright (C) JBZoo.com, All rights reserved.
 * @link       https://github.com/JBZoo/Composer-Diff
 */

namespace JBZoo\ComposerDiff;

use Composer\Semver\Comparator;

/**
 * Class Diff
 * @package JBZoo\ComposerDiff
 */
class Diff
{
    public const MODE_NEW        = 'New';
    public const MODE_REMOVED    = 'Removed';
    public const MODE_CHANGED    = 'Changed';
    public const MODE_UPGRADED   = 'Upgraded';
    public const MODE_DOWNGRADED = 'Downgraded';
    public const MODE_SAME       = 'Same';

    /**
     * @var string
     */
    private $mode = self::MODE_SAME;

    /**
     * @var string|null
     */
    private $comparingUrl;

    /**
     * @var Package|null
     */
    private $source;

    /**
     * @var Package|null
     */
    private $target;

    /**
     * Diff constructor.
     * @param Package|null $sourcePackage
     */
    public function __construct(?Package $sourcePackage = null)
    {
        $this->source = $sourcePackage;
    }

    /**
     * @param string $newMode
     * @return $this
     */
    public function setMode(string $newMode)
    {
        $this->mode = $newMode;
        return $this;
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        if ($this->source) {
            return [
                'name'         => $this->source->getName(),
                'url'          => $this->source->getPackageUrl(),
                'version_from' => $this->source->getVersion(true),
                'version_to'   => $this->target ? $this->target->getVersion(true) : null,
                'mode'         => $this->mode,
                'compare'      => $this->comparingUrl,
            ];
        }

        if ($this->target) {
            return [
                'name'         => $this->target->getName(),
                'url'          => $this->target->getPackageUrl(),
                'version_from' => null,
                'version_to'   => $this->target->getVersion(true),
                'mode'         => $this->mode,
                'compare'      => $this->comparingUrl,
            ];
        }

        throw new Exception('Source and target packages are not defined');
    }

    /**
     * @param Package $targetPackage
     * @return $this
     */
    public function compareWithPackage(Package $targetPackage)
    {
        $this->target = $targetPackage;

        if (!$this->source) {
            return $this->setMode(self::MODE_NEW);
        }

        if ($this->source->getName() !== $this->target->getName()) {
            throw new Exception("Can't compare versions of different packages. " .
                "Source:{$this->source->getName()}; Target:{$this->target->getName()};");
        }

        $sourceVersion = $this->source->getVersion();
        $targetVersion = $this->target->getVersion();
        $this->comparingUrl = $this->getComparingUrl($sourceVersion, $targetVersion);

        if ($sourceVersion === $targetVersion) {
            return $this->setMode(self::MODE_SAME);
        }

        if (self::isHashVersion($sourceVersion) || Diff::isHashVersion($targetVersion)) {
            return $this->setMode(self::MODE_CHANGED);
        }

        if (Comparator::greaterThan($sourceVersion, $targetVersion)) {
            return $this->setMode(self::MODE_DOWNGRADED);
        }

        if (Comparator::lessThan($sourceVersion, $targetVersion)) {
            return $this->setMode(self::MODE_UPGRADED);
        }

        return $this->setMode(self::MODE_CHANGED);
    }

    /**
     * @param string|null $fromVersion
     * @param string|null $toVersion
     * @return string|null
     */
    public function getComparingUrl(?string $fromVersion, ?string $toVersion): ?string
    {
        if (in_array($fromVersion, [self::MODE_REMOVED, self::MODE_NEW], true)) {
            return '';
        }

        if (in_array($toVersion, [self::MODE_REMOVED, self::MODE_NEW], true)) {
            return '';
        }

        if ($this->source) {
            return Url::getCompareUrl($this->source->getSourceUrl(), $fromVersion, $toVersion);
        }

        if ($this->target) {
            return Url::getCompareUrl($this->target->getSourceUrl(), $fromVersion, $toVersion);
        }

        throw new Exception('Source and target packages are not defined');
    }

    /**
     * @param string $version
     * @return bool
     */
    private static function isHashVersion(string $version): bool
    {
        return strlen($version) === Package::HASH_LENGTH && strpos($version, '.') === false;
    }
}
