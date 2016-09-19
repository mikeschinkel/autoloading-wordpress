<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
//          also https://github.com/JamesHeinrich/getID3       //
//                                                             //
//  FLV module by Seth Kaufman <sethØwhirl-i-gig*com>          //
//                                                             //
//  * version 0.1 (26 June 2005)                               //
//                                                             //
//                                                             //
//  * version 0.1.1 (15 July 2005)                             //
//  minor modifications by James Heinrich <info@getid3.org>    //
//                                                             //
//  * version 0.2 (22 February 2006)                           //
//  Support for On2 VP6 codec and meta information             //
//    by Steve Webster <steve.websterØfeaturecreep*com>        //
//                                                             //
//  * version 0.3 (15 June 2006)                               //
//  Modified to not read entire file into memory               //
//    by James Heinrich <info@getid3.org>                      //
//                                                             //
//  * version 0.4 (07 December 2007)                           //
//  Bugfixes for incorrectly parsed FLV dimensions             //
//    and incorrect parsing of onMetaTag                       //
//    by Evgeny Moysevich <moysevichØgmail*com>                //
//                                                             //
//  * version 0.5 (21 May 2009)                                //
//  Fixed parsing of audio tags and added additional codec     //
//    details. The duration is now read from onMetaTag (if     //
//    exists), rather than parsing whole file                  //
//    by Nigel Barnes <ngbarnesØhotmail*com>                   //
//                                                             //
//  * version 0.6 (24 May 2009)                                //
//  Better parsing of files with h264 video                    //
//    by Evgeny Moysevich <moysevichØgmail*com>                //
//                                                             //
//  * version 0.6.1 (30 May 2011)                              //
//    prevent infinite loops in expGolombUe()                  //
//                                                             //
//  * version 0.7.0 (16 Jul 2013)                              //
//  handle GETID3_FLV_VIDEO_VP6FLV_ALPHA                       //
//  improved AVCSequenceParameterSetReader::readData()         //
//    by Xander Schouwerwou <schouwerwouØgmail*com>            //
//                                                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio-video.flv.php                                  //
// module for analyzing Shockwave Flash Video files            //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////

class AVCSequenceParameterSetReader {
	public $sps;
	public $start = 0;
	public $currentBytes = 0;
	public $currentBits = 0;
	public $width;
	public $height;

	public function __construct($sps) {
		$this->sps = $sps;
	}

	public function readData() {
		$this->skipBits(8);
		$this->skipBits(8);
		$profile = $this->getBits(8);                               // read profile
		if ($profile > 0) {
			$this->skipBits(8);
			$level_idc = $this->getBits(8);                         // level_idc
			$this->expGolombUe();                                   // seq_parameter_set_id // sps
			$this->expGolombUe();                                   // log2_max_frame_num_minus4
			$picOrderType = $this->expGolombUe();                   // pic_order_cnt_type
			if ($picOrderType == 0) {
				$this->expGolombUe();                               // log2_max_pic_order_cnt_lsb_minus4
			} elseif ($picOrderType == 1) {
				$this->skipBits(1);                                 // delta_pic_order_always_zero_flag
				$this->expGolombSe();                               // offset_for_non_ref_pic
				$this->expGolombSe();                               // offset_for_top_to_bottom_field
				$num_ref_frames_in_pic_order_cnt_cycle = $this->expGolombUe(); // num_ref_frames_in_pic_order_cnt_cycle
				for ($i = 0; $i < $num_ref_frames_in_pic_order_cnt_cycle; $i++) {
					$this->expGolombSe();                           // offset_for_ref_frame[ i ]
				}
			}
			$this->expGolombUe();                                   // num_ref_frames
			$this->skipBits(1);                                     // gaps_in_frame_num_value_allowed_flag
			$pic_width_in_mbs_minus1 = $this->expGolombUe();        // pic_width_in_mbs_minus1
			$pic_height_in_map_units_minus1 = $this->expGolombUe(); // pic_height_in_map_units_minus1

			$frame_mbs_only_flag = $this->getBits(1);               // frame_mbs_only_flag
			if ($frame_mbs_only_flag == 0) {
				$this->skipBits(1);                                 // mb_adaptive_frame_field_flag
			}
			$this->skipBits(1);                                     // direct_8x8_inference_flag
			$frame_cropping_flag = $this->getBits(1);               // frame_cropping_flag

			$frame_crop_left_offset   = 0;
			$frame_crop_right_offset  = 0;
			$frame_crop_top_offset    = 0;
			$frame_crop_bottom_offset = 0;

			if ($frame_cropping_flag) {
				$frame_crop_left_offset   = $this->expGolombUe();   // frame_crop_left_offset
				$frame_crop_right_offset  = $this->expGolombUe();   // frame_crop_right_offset
				$frame_crop_top_offset    = $this->expGolombUe();   // frame_crop_top_offset
				$frame_crop_bottom_offset = $this->expGolombUe();   // frame_crop_bottom_offset
			}
			$this->skipBits(1);                                     // vui_parameters_present_flag
			// etc

			$this->width  = (($pic_width_in_mbs_minus1 + 1) * 16) - ($frame_crop_left_offset * 2) - ($frame_crop_right_offset * 2);
			$this->height = ((2 - $frame_mbs_only_flag) * ($pic_height_in_map_units_minus1 + 1) * 16) - ($frame_crop_top_offset * 2) - ($frame_crop_bottom_offset * 2);
		}
	}

	public function skipBits($bits) {
		$newBits = $this->currentBits + $bits;
		$this->currentBytes += (int)floor($newBits / 8);
		$this->currentBits = $newBits % 8;
	}

	public function getBit() {
		$result = (getid3_lib::BigEndian2Int(substr($this->sps, $this->currentBytes, 1)) >> (7 - $this->currentBits)) & 0x01;
		$this->skipBits(1);
		return $result;
	}

	public function getBits($bits) {
		$result = 0;
		for ($i = 0; $i < $bits; $i++) {
			$result = ($result << 1) + $this->getBit();
		}
		return $result;
	}

	public function expGolombUe() {
		$significantBits = 0;
		$bit = $this->getBit();
		while ($bit == 0) {
			$significantBits++;
			$bit = $this->getBit();

			if ($significantBits > 31) {
				// something is broken, this is an emergency escape to prevent infinite loops
				return 0;
			}
		}
		return (1 << $significantBits) + $this->getBits($significantBits) - 1;
	}

	public function expGolombSe() {
		$result = $this->expGolombUe();
		if (($result & 0x01) == 0) {
			return -($result >> 1);
		} else {
			return ($result + 1) >> 1;
		}
	}

	public function getWidth() {
		return $this->width;
	}

	public function getHeight() {
		return $this->height;
	}
}
