<?php

require_once 'Zend/Pdf.php';

/**
 *
 * Class to generate a (multipage) Invoice PDF (A5)
 *
 * Notes:
 * The total is derived from adding the items
 * Order lines are printed in order they appear in the items array.
 * Has supports for multi lines
 * VAT must be specified manually in usage example.
 * Prices support locale from Zend_Registry::get('Zend_Locale');
 *
 * usage:
 * <code>
 * $pdf = new Harkema_Pdf_Document_Invoice();
 * $pdf->generate($data);
 * $pdf->save($path);
 *
 *
 * $data = (object) array('date' => '21 mrt 2011',
 *                        'tableNumber' => '34',
 *                        'invoiceNumber' => '123',
 *                        'items' => array(
 *                            (object) array('amount' => '1', 'description' => 'Thee', 'price' => '1.90'),
 *                            (object) array('amount' => '11', 'description' => 'Bier', 'price' => '30.8'),
 *                            (object) array('amount' => '2', 'description' => 'Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source', 'price' => '45563.40'),
 *                            (object) array('amount' => '7', 'description' => 'Soep', 'price' => '46.55'),
 *                            ...
 *                            ),
 *                        'vat' => array('vat line 1',
 *                                       'vat line 2',
 *                                       ...
 *                                      )
 *                       );
 * </code>
 *
 * @author Bas Kamer <bas@bushbaby.nl>
 */
class Harkema_Pdf_Document_Invoice extends Harkema_Pdf_Document_Abstract {
    // points calculation (21.0cm * 72px / 2.54inch)
    const PAGE_HEIGHT = 595.275590; // 210mm
    const PAGE_WIDTH = 420.944881; // 148.5mm
    const FONT_SIZE = 8;
    const LINE_HEIGHT = 1.6;
    const MARGIN_LEFT = 25.5; // 9mm
    const MARGIN_RIGHT = 25.5; // 9mm
    const MARGIN_TOP = 198.425; // 70mm
    const MARGIN_BOTTOM = 113.385;  // 40mm

    private $data;
    private $cursorPosY;

    public function generate($data) {
        $this->data = $data;

        $this->cursorPosY = self::MARGIN_TOP;

        $page = new Zend_Pdf_Page(self::PAGE_WIDTH, self::PAGE_HEIGHT);
        $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), self::FONT_SIZE);
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));

        $this->drawHeader($page);

        foreach ($this->data->items as $item) {
            $drawn = $this->drawItem($item, $page);

            if (!$drawn) {
                $this->pages[] = $page;
                $this->cursorPosY = self::MARGIN_TOP;

                $page = new Zend_Pdf_Page(self::PAGE_WIDTH, self::PAGE_HEIGHT);
                $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_ITALIC), self::FONT_SIZE);
                $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
                $columnWidth = 20;
                $posY = Harkema_Pdf_Document_Abstract::drawTextBox($page, 'vervolg van ' . count($this->pages),
                                self::MARGIN_LEFT,
                                self::PAGE_HEIGHT - $this->cursorPosY,
                                self::PAGE_WIDTH - self::MARGIN_LEFT,
                                self::TEXT_ALIGN_LEFT, self::LINE_HEIGHT);

                $this->cursorPosY = self::PAGE_HEIGHT - $posY;
                // newline
                $this->cursorPosY += self::LINE_HEIGHT * self::FONT_SIZE;

                $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), self::FONT_SIZE);
                $drawn = $this->drawItem($item, $page);
            }
        }

        $this->drawFooter($page);
        $this->pages[] = $page;
    }

    public function drawHeader(Zend_Pdf_Page $page) {
        $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), self::FONT_SIZE);
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0.2));

        $columnLeft = 30;
        $columnWidth = 280;
        $posY = Harkema_Pdf_Document_Abstract::drawTextBox($page, 'Tafel',
                        self::MARGIN_LEFT, self::PAGE_HEIGHT - $this->cursorPosY, self::PAGE_WIDTH,
                        self::TEXT_ALIGN_LEFT, self::LINE_HEIGHT);

        $columnLeft = 30;
        $columnWidth = 280;
        $posY = Harkema_Pdf_Document_Abstract::drawTextBox($page, $this->data->tableNumber,
                        self::MARGIN_LEFT + $columnLeft, self::PAGE_HEIGHT - $this->cursorPosY, self::PAGE_WIDTH,
                        self::TEXT_ALIGN_LEFT, self::LINE_HEIGHT);

        $this->cursorPosY = self::PAGE_HEIGHT - $posY;

        $columnLeft = 30;
        $columnWidth = 280;
        $posY = Harkema_Pdf_Document_Abstract::drawTextBox($page, 'Datum',
                        self::MARGIN_LEFT, self::PAGE_HEIGHT - $this->cursorPosY, self::PAGE_WIDTH,
                        self::TEXT_ALIGN_LEFT, self::LINE_HEIGHT);

        $columnLeft = 30;
        $columnWidth = 280;
        $posY = Harkema_Pdf_Document_Abstract::drawTextBox($page, $this->data->date,
                        self::MARGIN_LEFT + $columnLeft, self::PAGE_HEIGHT - $this->cursorPosY, self::PAGE_WIDTH,
                        self::TEXT_ALIGN_LEFT, self::LINE_HEIGHT);

        $this->cursorPosY = self::PAGE_HEIGHT - $posY;

        // newline
        $this->cursorPosY += self::LINE_HEIGHT * self::FONT_SIZE;
    }

    public function drawItem($item, Zend_Pdf_Page $page) {

        // draw on an unutilzed page, so we can measure the resulting cursorPosY
        // and decide weater we would need a new page or not.
        $_page = new Zend_Pdf_Page(self::PAGE_WIDTH, self::PAGE_HEIGHT);
        $_page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), self::FONT_SIZE);
        $_page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $columnLeft = 30;
        $columnWidth = 280;
        $height = Harkema_Pdf_Document_Abstract::drawTextBox($_page, $item->description,
                        self::MARGIN_LEFT + $columnLeft,
                        self::PAGE_HEIGHT - $this->cursorPosY,
                        self::MARGIN_LEFT + $columnLeft + $columnWidth,
                        self::TEXT_ALIGN_LEFT, self::LINE_HEIGHT);

        if ($this->cursorPosY - $height > self::PAGE_HEIGHT - self::MARGIN_TOP)
            return false;


        $columnWidth = 20;
        $posY = Harkema_Pdf_Document_Abstract::drawTextBox($page, $item->amount,
                        self::MARGIN_LEFT, self::PAGE_HEIGHT - $this->cursorPosY, self::MARGIN_LEFT + $columnWidth,
                        self::TEXT_ALIGN_RIGHT, self::LINE_HEIGHT);

        $columnLeft = 30;
        $columnWidth = 280;
        $posY = Harkema_Pdf_Document_Abstract::drawTextBox($page, $item->description,
                        self::MARGIN_LEFT + $columnLeft,
                        self::PAGE_HEIGHT - $this->cursorPosY,
                        self::MARGIN_LEFT + $columnLeft + $columnWidth,
                        self::TEXT_ALIGN_LEFT, self::LINE_HEIGHT);

        $c = new Zend_Currency();
        $posY = Harkema_Pdf_Document_Abstract::drawTextBox($page, $c->toCurrency($item->price),
                        self::MARGIN_LEFT, $posY + (self::FONT_SIZE * self::LINE_HEIGHT),
                        self::PAGE_WIDTH - self::MARGIN_RIGHT,
                        self::TEXT_ALIGN_RIGHT, self::LINE_HEIGHT);

        $this->cursorPosY = self::PAGE_HEIGHT - $posY;

        return true;
    }

    protected function drawFooter(Zend_Pdf_Page $page) {
        $this->data->priceTotal = 0;
        foreach ($this->data->items as $item)
            $this->data->priceTotal += $item->price;

        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0));
        $page->setLineWidth(.1);
        $page->drawLine(
                self::MARGIN_LEFT /* x1 */,
                self::PAGE_HEIGHT - $this->cursorPosY + self::FONT_SIZE /* y1 */,
                self::PAGE_WIDTH - self::MARGIN_RIGHT /* x2 */,
                self::PAGE_HEIGHT - $this->cursorPosY + self::FONT_SIZE /* y2 */);

        $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), self::FONT_SIZE);

        $c = new Zend_Currency();

        Harkema_Pdf_Document_Abstract::drawText($page, ' Totaal ' . $c->toCurrency($this->data->priceTotal),
                        self::MARGIN_LEFT,
                        self::PAGE_HEIGHT - $this->cursorPosY - 2,
                        self::PAGE_WIDTH - self::MARGIN_RIGHT,
                        self::TEXT_ALIGN_RIGHT);

        if (isset($this->data->vat) && is_array($this->data->vat)) {
            $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), self::FONT_SIZE);

            foreach($this->data->vat as $line) {
            // newline
            $this->cursorPosY += self::LINE_HEIGHT * self::FONT_SIZE;

            Harkema_Pdf_Document_Abstract::drawText($page, $line,
                            self::MARGIN_LEFT,
                            self::PAGE_HEIGHT - $this->cursorPosY,
                            self::PAGE_WIDTH - self::MARGIN_RIGHT,
                            self::TEXT_ALIGN_LEFT);
                    }
        }

        if (isset($this->data->remark)) {
            // newline
            $this->cursorPosY += self::LINE_HEIGHT * self::FONT_SIZE;
            // newline
            $this->cursorPosY += self::LINE_HEIGHT * self::FONT_SIZE;

            $page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_ITALIC), self::FONT_SIZE);
            Harkema_Pdf_Document_Abstract::drawText($page, $this->data->remark,
                            self::MARGIN_LEFT,
                            self::PAGE_HEIGHT - $this->cursorPosY,
                            self::PAGE_WIDTH - self::MARGIN_RIGHT,
                            self::TEXT_ALIGN_LEFT);
        }
    }

}