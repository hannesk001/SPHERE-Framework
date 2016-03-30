<?php
namespace SPHERE\Application\Education\Graduation\Certificate\Repository;

use SPHERE\System\Extension\Extension;

abstract class Style extends Extension
{

    /** @var array $Design */
    protected $Design = array();
    /** @var array $Style */
    protected $Style = array();

    /**
     * @param string $Color
     *
     * @return $this
     */
    public function styleTextColor($Color)
    {

        $this->Style[] = 'color: '.$Color.' !important;';
        return $this;
    }

    /**
     * @param string $Color
     *
     * @return $this
     */
    public function styleBackgroundColor($Color)
    {

        $this->Style[] = 'background-color: '.$Color.' !important;';
        return $this;
    }

    /**
     * @param string $Size
     *
     * @return $this
     */
    public function styleTextSize($Size)
    {

        $this->Style[] = 'font-size: '.$Size.' !important;';
        return $this;
    }

    /**
     * @param string $Weight
     *
     * @return $this
     */
    public function styleTextBold($Weight = 'bold')
    {

        $this->Style[] = 'font-weight: '.$Weight.' !important;';
        return $this;
    }

    /**
     * @return $this
     */
    public function styleTextItalic()
    {

        $this->Style[] = 'font-style:italic !important;';
        return $this;
    }

    /**
     * @param string $Size
     *
     * @return $this
     */
    public function styleMarginTop($Size)
    {

        $this->Style[] = 'margin-top: '.$Size.' !important;';
        return $this;
    }

    /**
     * @param string $Size
     *
     * @return $this
     */
    public function styleMarginBottom($Size)
    {

        $this->Style[] = 'margin-bottom: '.$Size.' !important;';
        return $this;
    }

    /**
     * @param string $Size
     * @param string $Color
     * @param string $Style
     *
     * @return $this
     */
    public function styleBorderTop($Size = '1px', $Color = '#000', $Style = 'solid')
    {

        $this->Style[] = 'border-top: '.$Size.' '.$Style.' '.$Color.' !important;';
        return $this;
    }

    /**
     * @param string $Size
     * @param string $Color
     * @param string $Style
     *
     * @return $this
     */
    public function styleBorderBottom($Size = '1px', $Color = '#000', $Style = 'solid')
    {

        $this->Style[] = 'border-bottom: '.$Size.' '.$Style.' '.$Color.' !important;';
        return $this;
    }

    /**
     * @param string $Size
     *
     * @return $this
     */
    public function stylePaddingBottom($Size = '2px')
    {

        $this->Style[] = 'padding-bottom: '.$Size.' !important;';
        return $this;
    }

    /**
     * @param string $Size
     *
     * @return $this
     */
    public function stylePaddingTop($Size = '2px')
    {

        $this->Style[] = 'padding-top: '.$Size.' !important;';
        return $this;
    }

    /**
     * @return $this
     */
    public function styleAlignLeft()
    {

        $this->Design[] = 'Align Left';
        return $this;
    }

    /**
     * @return $this
     */
    public function styleAlignRight()
    {

        $this->Design[] = 'Align Right';
        return $this;
    }

    /**
     * @return $this
     */
    public function styleAlignCenter()
    {

        $this->Design[] = 'Align Center';
        return $this;
    }
}