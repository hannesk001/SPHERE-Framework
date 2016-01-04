<?php
namespace SPHERE\Application\Document\Designer\Repository\Panel;

use MOC\V\Component\Template\Component\IBridgeInterface;
use MOC\V\Component\Template\Template;
use SPHERE\Common\Frontend\ITemplateInterface;

/**
 * Class Repository
 *
 * @package SPHERE\Application\Document\Designer\Repository\Panel
 */
class Repository implements ITemplateInterface
{

    /** @var IBridgeInterface|null $Template */
    private $Template = null;
    /** @var null|string */
    private $Content = null;

    /**
     * Repository constructor.
     *
     * @param null|string $Content
     */
    function __construct($Content = null)
    {

        $this->Content = $Content;
        $this->Template = Template::getTwigTemplateString(
            '<div class="SDD-Panel SDD-Repository">'
            .'{{ Content }}'
            .'</div>'
        );
    }

    /**
     * @return string
     */
    public function __toString()
    {

        return (string)$this->getContent();
    }

    /**
     * @return string
     */
    public function getContent()
    {

        if (is_array($this->Content)) {
            $this->Content = implode($this->Content);
        }

        $this->Template->setVariable('Content', $this->Content);

        return $this->Template->getContent();
    }

    /**
     * @param null|string $Content
     */
    public function setContent($Content)
    {

        $this->Content = $Content;
    }
}
