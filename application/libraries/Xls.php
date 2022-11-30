<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

use PhpOffice\PhpSpreadsheet\Helper\Sample;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Xls
{
  private $data = [];

  private $headers = [];

  private $filename = null;

  private $handler = null;

  private $activeSheet = null;

  private $title = 'Findable Report';

  private $creator = 'Findable Platform';

  public function __construct($options = []) 
  {
    $this->filename = isset($options['filename']) && !empty($options['filename']) ? $options['filename'] : '';

    $this->handler = new Spreadsheet();
  }

  /**
	 * __get
	 *
	 * Enables the use of CI super-global without having to define an extra variable.
	 *
	 * @access    public
	 *
	 * @params    $var
	 *
	 * @return    mixed
	 */
  public function __get( $name ) 
  {
    if ( isset( $this->$name ) ) 
    {
			return $this->$name;
		} else {
			return get_instance()->$name;
		}
  }
  
  public function excel($headers = [], $data = [])
  {
    $_keys = array_keys($headers);

    // Set document properties
    $this->handler->getProperties()->setCreator($this->creator)
        ->setLastModifiedBy($this->creator)
        ->setTitle($this->title);

    $this->activeSheet = $this->handler->setActiveSheetIndex(0);
    foreach(array_keys($headers) as $index => $key)
    {
      $this->activeSheet->setCellValue($this->toAlpha($index, true) . "1", $headers[$key]);
    }

    $rowIterator = 2;

    foreach($data as $key => $item)
    {
      for($columnIterator = 0; $columnIterator < count(array_keys($headers)); $columnIterator++)
      {
        $_item = is_array($item) ? $item[$_keys[$columnIterator]] : $item->{$_keys[$columnIterator]};

        $this->activeSheet->setCellValue($this->toAlpha($columnIterator, true) . $rowIterator, $_item);
      }
      $rowIterator++;
    }

    return $this;
  }

  public function download()
  {
    // Redirect output to a client’s web browser (Xls)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="01simple.xls"');
    header('Cache-Control: max-age=0');
    // If you're serving to IE 9, then the following may be needed
    header('Cache-Control: max-age=1');
    // If you're serving to IE over SSL, then the following may be needed
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
    header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header('Pragma: public'); // HTTP/1.0
    $writer = IOFactory::createWriter($this->handler, 'Xls');
    $writer->save('php://output');
    exit;
  }

  private function toAlpha($data, $toUpper = false)
  {
    $alphabet =   array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
    $alpha_flip = array_flip($alphabet);
    if($data <= 25)
    {
      return $toUpper ? strtoupper($alphabet[$data]) : $alphabet[$data];
    } 
    elseif($data > 25)
    {
      $dividend = ($data + 1);
      $alpha = '';
      $modulo;
      while ($dividend > 0)
      {
        $modulo = ($dividend - 1) % 26;
        $alpha = $toUpper ? strstoupper($alphabet[$modulo] . $alpha) : $alphabet[$modulo] . $alpha;
        $dividend = floor((($dividend - $modulo) / 26));
      } 
      return $alpha;
    }
  }
}