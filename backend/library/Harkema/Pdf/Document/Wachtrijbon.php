<?php

require_once 'Zend/Pdf.php';

class Harkema_Pdf_Document_Wachtrijbon extends Harkema_Pdf_Document_Abstract {
    // points calculation (21.0cm * 72px / 2.54inch)
    static $PAGE_HEIGHT = 0;
    const PAGE_WIDTH = 218.267716; // 77mm
    const FONT_SIZE = 8;
    const LINE_HEIGHT = 1.4;
    const MARGIN_LEFT = 11.338581; // 4mm
    const MARGIN_RIGHT = 11.338581; // 4mm
    const MARGIN_TOP = 11.338581; // 4mm
    const MARGIN_BOTTOM = 11.338581; // 4mm

    private $data;
    private $cursorPosY;

    public function generate($data) {
        $this->data = $data;

        do {
            $this->cursorPosY = self::MARGIN_TOP;
            $page = new Zend_Pdf_Page(self::PAGE_WIDTH, self::$PAGE_HEIGHT);

            $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), self::FONT_SIZE);
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
            foreach ($this->data->items as $item) {
                $this->drawItem($item, $page);
            }

            $this->cursorPosY -= (self::LINE_HEIGHT * self::FONT_SIZE);// remove last line

            if (self::$PAGE_HEIGHT == 0)
                self::$PAGE_HEIGHT = $this->cursorPosY + self::MARGIN_BOTTOM;
            else
                break;
        } while(true);

        $this->pages[] = $page;
    }

    public function drawItem($item, Zend_Pdf_Page $page) {
        $columnLeft = 0;
        $columnWidth = 15;
        $posY = Harkema_Pdf_Document_Abstract::drawTextBox($page, $item->amount,
                        self::MARGIN_LEFT + $columnLeft, self::$PAGE_HEIGHT - $this->cursorPosY, self::MARGIN_LEFT + $columnLeft + $columnWidth,
                        self::TEXT_ALIGN_RIGHT, self::LINE_HEIGHT);
    
        $columnLeft += 20;
        $columnWidth = 480;
        $posY = Harkema_Pdf_Document_Abstract::drawTextBox($page, $item->description . (isset($item->remark) ? ' (' . $item->remark . ')' : ''),
                        self::MARGIN_LEFT + $columnLeft,
                        self::$PAGE_HEIGHT - $this->cursorPosY,
                        self::PAGE_WIDTH - self::MARGIN_RIGHT,
                        self::TEXT_ALIGN_LEFT, self::LINE_HEIGHT);

        $this->cursorPosY = self::$PAGE_HEIGHT - $posY;

        return true;
    }

  

}