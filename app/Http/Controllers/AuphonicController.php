<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuphonicController extends Controller
{
	public function processAudio(Request $request)
	{
		$filePath = asset('test.mp3');

		if ($filePath) {
			$data = [
				'algorithms' => [
					'leveler' => true,
					'levelerstrength' => 100,
					'compressor_speech' => 'off',
					'compressor_music' => 'off',
					'musicgain' => 0,
					'msclassifier' => 'off',
					'maxlra' => 0,
					'maxs' => 0,
					'maxm' => 0,
					'denoise' => true,
					'denoiseamount' => 0,
					'dehum' => 0,
					'dehumamount' => 0,
					'normloudness' => true,
					'loudnesstarget' => -24,
					'maxpeak' => -2,
					'dualmono' => false,
					'loudnessmethod' => 'dialog',
					'silence_cutter' => false,
					'filler_cutter' => false,
					'export_uncut_audio' => false
				]
			];

			$response = Http::withBasicAuth('alimohdrajwa5', '7y4Ut!QhyZYtFtE')
			                ->attach('input_file', file_get_contents($filePath))
			                ->post('https://auphonic.com/api/production/.json', $data);

			if ($response->successful()) {
				return $response->json();
			} else {
				return response()->json(['error' => 'Failed to process audio.'], 500);
			}
		} else {
			return response()->json(['error' => 'Invalid or missing audio file.'], 400);
		}
	}
}
