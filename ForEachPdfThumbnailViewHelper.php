<?php
namespace Conversion\HelperUtils\ViewHelpers;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/***
 *
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Mikel Wohlschlegel <mw@con-version.de>, conversion
 *
 ***/

class ForEachPdfThumbnailViewHelper extends AbstractViewHelper
{

    use CompileWithRenderStatic;

    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('document', 'TYPO3\CMS\Core\Resource\FileReference', '', true);
        $this->registerArgument('pages', 'string', '', true);
        $this->registerArgument('as', 'string', 'The name of the iteration variable', true);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     * @throws ViewHelper\Exception
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {

        $templateVariableContainer = $renderingContext->getVariableProvider();

        /** @var \TYPO3\CMS\Core\Resource\FileReference $document */
        $document = $arguments['document'];
        $pages = explode(',', str_replace(' ', '', $arguments['pages']));

        $colorspace = TRUE === isset($GLOBALS['TYPO3_CONF_VARS']['GFX']['colorspace']) ? $GLOBALS['TYPO3_CONF_VARS']['GFX']['colorspace'] : 'RGB';
        $absFilePath = GeneralUtility::getFileAbsFileName($document->getOriginalFile()->getPublicUrl());
        $destinationPath = 'typo3temp/';
        $destinationFilePrefix = 'pdf-prev_' . $document->getOriginalFile()->getNameWithoutExtension();
        $destinationFileExtension = 'png';

        $output = '';

        foreach ($pages as $pageNumber) {

            if($pageNumber > 0) {
                $pageNumber = intval($pageNumber);
            } else {
                $pageNumber = 1;
            }

            $destinationFileSuffix =  '_page-' . $pageNumber;
            $absDestinationFilePath = GeneralUtility::getFileAbsFileName($destinationPath . $destinationFilePrefix . $destinationFileSuffix . '.' . $destinationFileExtension);

            $imgArguments = '-colorspace ' . $colorspace;
            $imgArguments .= ' -density 300';
            $imgArguments .= ' -sharpen 0x.6';
            $imgArguments .= ' "' . $absFilePath . '"';
            $imgArguments .= '['. intval($pageNumber - 1) .']';
            $imgArguments .= ' "' . $absDestinationFilePath . '"';

            if(!file_exists($absDestinationFilePath)) {
                $command = CommandUtility::imageMagickCommand('convert', $imgArguments);
                CommandUtility::exec($command);
            }

            $thumbnail = substr($absDestinationFilePath, strlen(Environment::getPublicPath()));
            $templateVariableContainer->add($arguments['as'], $thumbnail);
            $output .= $renderChildrenClosure();
            $templateVariableContainer->remove($arguments['as']);

        }

        return $output;
    }

}
