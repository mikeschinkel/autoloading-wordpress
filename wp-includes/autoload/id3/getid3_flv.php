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

class getid3_flv extends getid3_handler {

	const magic = 'FLV';

	public $max_frames = 100000; // break out of the loop if too many frames have been scanned; only scan this many if meta frame does not contain useful duration

	public function Analyze() {
		$info = &$this->getid3->info;

		$this->fseek($info['avdataoffset']);

		$FLVdataLength = $info['avdataend'] - $info['avdataoffset'];
		$FLVheader = $this->fread(5);

		$info['fileformat'] = 'flv';
		$info['flv']['header']['signature'] =                           substr($FLVheader, 0, 3);
		$info['flv']['header']['version']   = getid3_lib::BigEndian2Int(substr($FLVheader, 3, 1));
		$TypeFlags                          = getid3_lib::BigEndian2Int(substr($FLVheader, 4, 1));

		if ($info['flv']['header']['signature'] != self::magic) {
			$info['error'][] = 'Expecting "'.getid3_lib::PrintHexBytes(self::magic).'" at offset '.$info['avdataoffset'].', found "'.getid3_lib::PrintHexBytes($info['flv']['header']['signature']).'"';
			unset($info['flv'], $info['fileformat']);
			return false;
		}

		$info['flv']['header']['hasAudio'] = (bool) ($TypeFlags & 0x04);
		$info['flv']['header']['hasVideo'] = (bool) ($TypeFlags & 0x01);

		$FrameSizeDataLength = getid3_lib::BigEndian2Int($this->fread(4));
		$FLVheaderFrameLength = 9;
		if ($FrameSizeDataLength > $FLVheaderFrameLength) {
			$this->fseek($FrameSizeDataLength - $FLVheaderFrameLength, SEEK_CUR);
		}
		$Duration = 0;
		$found_video = false;
		$found_audio = false;
		$found_meta  = false;
		$found_valid_meta_playtime = false;
		$tagParseCount = 0;
		$info['flv']['framecount'] = array('total'=>0, 'audio'=>0, 'video'=>0);
		$flv_framecount = &$info['flv']['framecount'];
		while ((($this->ftell() + 16) < $info['avdataend']) && (($tagParseCount++ <= $this->max_frames) || !$found_valid_meta_playtime))  {
			$ThisTagHeader = $this->fread(16);

			$PreviousTagLength = getid3_lib::BigEndian2Int(substr($ThisTagHeader,  0, 4));
			$TagType           = getid3_lib::BigEndian2Int(substr($ThisTagHeader,  4, 1));
			$DataLength        = getid3_lib::BigEndian2Int(substr($ThisTagHeader,  5, 3));
			$Timestamp         = getid3_lib::BigEndian2Int(substr($ThisTagHeader,  8, 3));
			$LastHeaderByte    = getid3_lib::BigEndian2Int(substr($ThisTagHeader, 15, 1));
			$NextOffset = $this->ftell() - 1 + $DataLength;
			if ($Timestamp > $Duration) {
				$Duration = $Timestamp;
			}

			$flv_framecount['total']++;
			switch ($TagType) {
				case GETID3_FLV_TAG_AUDIO:
					$flv_framecount['audio']++;
					if (!$found_audio) {
						$found_audio = true;
						$info['flv']['audio']['audioFormat']     = ($LastHeaderByte >> 4) & 0x0F;
						$info['flv']['audio']['audioRate']       = ($LastHeaderByte >> 2) & 0x03;
						$info['flv']['audio']['audioSampleSize'] = ($LastHeaderByte >> 1) & 0x01;
						$info['flv']['audio']['audioType']       =  $LastHeaderByte       & 0x01;
					}
					break;

				case GETID3_FLV_TAG_VIDEO:
					$flv_framecount['video']++;
					if (!$found_video) {
						$found_video = true;
						$info['flv']['video']['videoCodec'] = $LastHeaderByte & 0x07;

						$FLVvideoHeader = $this->fread(11);

						if ($info['flv']['video']['videoCodec'] == GETID3_FLV_VIDEO_H264) {
							// this code block contributed by: moysevichØgmail*com

							$AVCPacketType = getid3_lib::BigEndian2Int(substr($FLVvideoHeader, 0, 1));
							if ($AVCPacketType == H264_AVC_SEQUENCE_HEADER) {
								//	read AVCDecoderConfigurationRecord
								$configurationVersion       = getid3_lib::BigEndian2Int(substr($FLVvideoHeader,  4, 1));
								$AVCProfileIndication       = getid3_lib::BigEndian2Int(substr($FLVvideoHeader,  5, 1));
								$profile_compatibility      = getid3_lib::BigEndian2Int(substr($FLVvideoHeader,  6, 1));
								$lengthSizeMinusOne         = getid3_lib::BigEndian2Int(substr($FLVvideoHeader,  7, 1));
								$numOfSequenceParameterSets = getid3_lib::BigEndian2Int(substr($FLVvideoHeader,  8, 1));

								if (($numOfSequenceParameterSets & 0x1F) != 0) {
									//	there is at least one SequenceParameterSet
									//	read size of the first SequenceParameterSet
									//$spsSize = getid3_lib::BigEndian2Int(substr($FLVvideoHeader, 9, 2));
									$spsSize = getid3_lib::LittleEndian2Int(substr($FLVvideoHeader, 9, 2));
									//	read the first SequenceParameterSet
									$sps = $this->fread($spsSize);
									if (strlen($sps) == $spsSize) {	//	make sure that whole SequenceParameterSet was red
										$spsReader = new AVCSequenceParameterSetReader($sps);
										$spsReader->readData();
										$info['video']['resolution_x'] = $spsReader->getWidth();
										$info['video']['resolution_y'] = $spsReader->getHeight();
									}
								}
							}
							// end: moysevichØgmail*com

						} elseif ($info['flv']['video']['videoCodec'] == GETID3_FLV_VIDEO_H263) {

							$PictureSizeType = (getid3_lib::BigEndian2Int(substr($FLVvideoHeader, 3, 2))) >> 7;
							$PictureSizeType = $PictureSizeType & 0x0007;
							$info['flv']['header']['videoSizeType'] = $PictureSizeType;
							switch ($PictureSizeType) {
								case 0:
									//$PictureSizeEnc = getid3_lib::BigEndian2Int(substr($FLVvideoHeader, 5, 2));
									//$PictureSizeEnc <<= 1;
									//$info['video']['resolution_x'] = ($PictureSizeEnc & 0xFF00) >> 8;
									//$PictureSizeEnc = getid3_lib::BigEndian2Int(substr($FLVvideoHeader, 6, 2));
									//$PictureSizeEnc <<= 1;
									//$info['video']['resolution_y'] = ($PictureSizeEnc & 0xFF00) >> 8;

									$PictureSizeEnc['x'] = getid3_lib::BigEndian2Int(substr($FLVvideoHeader, 4, 2)) >> 7;
									$PictureSizeEnc['y'] = getid3_lib::BigEndian2Int(substr($FLVvideoHeader, 5, 2)) >> 7;
									$info['video']['resolution_x'] = $PictureSizeEnc['x'] & 0xFF;
									$info['video']['resolution_y'] = $PictureSizeEnc['y'] & 0xFF;
									break;

								case 1:
									$PictureSizeEnc['x'] = getid3_lib::BigEndian2Int(substr($FLVvideoHeader, 4, 3)) >> 7;
									$PictureSizeEnc['y'] = getid3_lib::BigEndian2Int(substr($FLVvideoHeader, 6, 3)) >> 7;
									$info['video']['resolution_x'] = $PictureSizeEnc['x'] & 0xFFFF;
									$info['video']['resolution_y'] = $PictureSizeEnc['y'] & 0xFFFF;
									break;

								case 2:
									$info['video']['resolution_x'] = 352;
									$info['video']['resolution_y'] = 288;
									break;

								case 3:
									$info['video']['resolution_x'] = 176;
									$info['video']['resolution_y'] = 144;
									break;

								case 4:
									$info['video']['resolution_x'] = 128;
									$info['video']['resolution_y'] = 96;
									break;

								case 5:
									$info['video']['resolution_x'] = 320;
									$info['video']['resolution_y'] = 240;
									break;

								case 6:
									$info['video']['resolution_x'] = 160;
									$info['video']['resolution_y'] = 120;
									break;

								default:
									$info['video']['resolution_x'] = 0;
									$info['video']['resolution_y'] = 0;
									break;

							}

						} elseif ($info['flv']['video']['videoCodec'] ==  GETID3_FLV_VIDEO_VP6FLV_ALPHA) {

							/* contributed by schouwerwouØgmail*com */
							if (!isset($info['video']['resolution_x'])) { // only when meta data isn't set
								$PictureSizeEnc['x'] = getid3_lib::BigEndian2Int(substr($FLVvideoHeader, 6, 2));
								$PictureSizeEnc['y'] = getid3_lib::BigEndian2Int(substr($FLVvideoHeader, 7, 2));
								$info['video']['resolution_x'] = ($PictureSizeEnc['x'] & 0xFF) << 3;
								$info['video']['resolution_y'] = ($PictureSizeEnc['y'] & 0xFF) << 3;
							}
							/* end schouwerwouØgmail*com */

						}
						if (!empty($info['video']['resolution_x']) && !empty($info['video']['resolution_y'])) {
							$info['video']['pixel_aspect_ratio'] = $info['video']['resolution_x'] / $info['video']['resolution_y'];
						}
					}
					break;

				// Meta tag
				case GETID3_FLV_TAG_META:
					if (!$found_meta) {
						$found_meta = true;
						$this->fseek(-1, SEEK_CUR);
						$datachunk = $this->fread($DataLength);
						$AMFstream = new AMFStream($datachunk);
						$reader = new AMFReader($AMFstream);
						$eventName = $reader->readData();
						$info['flv']['meta'][$eventName] = $reader->readData();
						unset($reader);

						$copykeys = array('framerate'=>'frame_rate', 'width'=>'resolution_x', 'height'=>'resolution_y', 'audiodatarate'=>'bitrate', 'videodatarate'=>'bitrate');
						foreach ($copykeys as $sourcekey => $destkey) {
							if (isset($info['flv']['meta']['onMetaData'][$sourcekey])) {
								switch ($sourcekey) {
									case 'width':
									case 'height':
										$info['video'][$destkey] = intval(round($info['flv']['meta']['onMetaData'][$sourcekey]));
										break;
									case 'audiodatarate':
										$info['audio'][$destkey] = getid3_lib::CastAsInt(round($info['flv']['meta']['onMetaData'][$sourcekey] * 1000));
										break;
									case 'videodatarate':
									case 'frame_rate':
									default:
										$info['video'][$destkey] = $info['flv']['meta']['onMetaData'][$sourcekey];
										break;
								}
							}
						}
						if (!empty($info['flv']['meta']['onMetaData']['duration'])) {
							$found_valid_meta_playtime = true;
						}
					}
					break;

				default:
					// noop
					break;
			}
			$this->fseek($NextOffset);
		}

		$info['playtime_seconds'] = $Duration / 1000;
		if ($info['playtime_seconds'] > 0) {
			$info['bitrate'] = (($info['avdataend'] - $info['avdataoffset']) * 8) / $info['playtime_seconds'];
		}

		if ($info['flv']['header']['hasAudio']) {
			$info['audio']['codec']           =   self::audioFormatLookup($info['flv']['audio']['audioFormat']);
			$info['audio']['sample_rate']     =     self::audioRateLookup($info['flv']['audio']['audioRate']);
			$info['audio']['bits_per_sample'] = self::audioBitDepthLookup($info['flv']['audio']['audioSampleSize']);

			$info['audio']['channels']   =  $info['flv']['audio']['audioType'] + 1; // 0=mono,1=stereo
			$info['audio']['lossless']   = ($info['flv']['audio']['audioFormat'] ? false : true); // 0=uncompressed
			$info['audio']['dataformat'] = 'flv';
		}
		if (!empty($info['flv']['header']['hasVideo'])) {
			$info['video']['codec']      = self::videoCodecLookup($info['flv']['video']['videoCodec']);
			$info['video']['dataformat'] = 'flv';
			$info['video']['lossless']   = false;
		}

		// Set information from meta
		if (!empty($info['flv']['meta']['onMetaData']['duration'])) {
			$info['playtime_seconds'] = $info['flv']['meta']['onMetaData']['duration'];
			$info['bitrate'] = (($info['avdataend'] - $info['avdataoffset']) * 8) / $info['playtime_seconds'];
		}
		if (isset($info['flv']['meta']['onMetaData']['audiocodecid'])) {
			$info['audio']['codec'] = self::audioFormatLookup($info['flv']['meta']['onMetaData']['audiocodecid']);
		}
		if (isset($info['flv']['meta']['onMetaData']['videocodecid'])) {
			$info['video']['codec'] = self::videoCodecLookup($info['flv']['meta']['onMetaData']['videocodecid']);
		}
		return true;
	}


	public static function audioFormatLookup($id) {
		static $lookup = array(
			0  => 'Linear PCM, platform endian',
			1  => 'ADPCM',
			2  => 'mp3',
			3  => 'Linear PCM, little endian',
			4  => 'Nellymoser 16kHz mono',
			5  => 'Nellymoser 8kHz mono',
			6  => 'Nellymoser',
			7  => 'G.711A-law logarithmic PCM',
			8  => 'G.711 mu-law logarithmic PCM',
			9  => 'reserved',
			10 => 'AAC',
			11 => 'Speex',
			12 => false, // unknown?
			13 => false, // unknown?
			14 => 'mp3 8kHz',
			15 => 'Device-specific sound',
		);
		return (isset($lookup[$id]) ? $lookup[$id] : false);
	}

	public static function audioRateLookup($id) {
		static $lookup = array(
			0 =>  5500,
			1 => 11025,
			2 => 22050,
			3 => 44100,
		);
		return (isset($lookup[$id]) ? $lookup[$id] : false);
	}

	public static function audioBitDepthLookup($id) {
		static $lookup = array(
			0 =>  8,
			1 => 16,
		);
		return (isset($lookup[$id]) ? $lookup[$id] : false);
	}

	public static function videoCodecLookup($id) {
		static $lookup = array(
			GETID3_FLV_VIDEO_H263         => 'Sorenson H.263',
			GETID3_FLV_VIDEO_SCREEN       => 'Screen video',
			GETID3_FLV_VIDEO_VP6FLV       => 'On2 VP6',
			GETID3_FLV_VIDEO_VP6FLV_ALPHA => 'On2 VP6 with alpha channel',
			GETID3_FLV_VIDEO_SCREENV2     => 'Screen video v2',
			GETID3_FLV_VIDEO_H264         => 'Sorenson H.264',
		);
		return (isset($lookup[$id]) ? $lookup[$id] : false);
	}
}
