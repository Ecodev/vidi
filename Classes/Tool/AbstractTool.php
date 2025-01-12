<?php

namespace Fab\Vidi\Tool;

/*
 * This file is part of the Fab/Vidi project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use Fab\Vidi\Module\ModuleLoader;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Abstract Tool
 */
abstract class AbstractTool implements ToolInterface
{
    /**
     * @param string $templateNameAndPath
     * @return StandaloneView
     * @throws \InvalidArgumentException
     */
    protected function initializeStandaloneView($templateNameAndPath)
    {
        $templateNameAndPath = GeneralUtility::getFileAbsFileName($templateNameAndPath);

        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);

        $view->setTemplatePathAndFilename($templateNameAndPath);
        return $view;
    }

    /**
     * Returns an instance of the current Backend User.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Get the Vidi Module Loader.
     *
     * @return ModuleLoader
     * @throws \InvalidArgumentException
     */
    protected function getModuleLoader()
    {
        return GeneralUtility::makeInstance(ModuleLoader::class);
    }

    /**
     * Returns whether the current mode is Backend
     */
    public static function isBackend(): bool
    {
        return ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend();
    }

    /**
     * Returns whether the current mode is Frontend
     */
    public static function isFrontend(): bool
    {
        return ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend();
    }
}
