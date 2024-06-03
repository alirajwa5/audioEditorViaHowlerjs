<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use FFMpeg\Filters\Audio\SimpleFilter;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use FFMpeg\Coordinate\TimeCode;
class AudioController extends Controller
{
	public function mergeAudio(Request $request)
	{
		$files = $request->file('audio_files');
		$outputPath = storage_path('app/public/merged_audio.mp3');

		$ffmpeg = FFMpeg::create();
		$audio = $ffmpeg->open($files[0]->getPathname());

		for ($i = 1; $i < count($files); $i++) {
			$audio->addFilter(new \FFMpeg\Filters\Audio\ConcatAllFilter([$files[$i]->getPathname()]));
		}

		$audio->save(new \FFMpeg\Format\Audio\Mp3(), $outputPath);

		return response()->download($outputPath);
	}

	public function addBackgroundMusic(Request $request)
	{
		$audioFile = $request->file('audio');
		$backgroundFile = $request->file('background');
		$outputPath = storage_path('app/public/audio_with_bg.mp3');

		$ffmpeg = FFMpeg::create();
		$audio = $ffmpeg->open($audioFile->getPathname());

		$audio->addFilter(new \FFMpeg\Filters\Audio\SimpleFilter(['-filter_complex', '[0][1]amix=inputs=2:duration=first:dropout_transition=3']));

		$audio->save(new \FFMpeg\Format\Audio\Mp3(), $outputPath);

		return response()->download($outputPath);
	}

	public function cropAudio(Request $request)
	{
		$audioFile = $request->file('audio');
		$startTime = $request->input('start_time'); // e.g., '00:00:10'
		$duration = $request->input('duration'); // e.g., '00:00:30'
		$outputPath = storage_path('app/public/cropped_audio.mp3');



		$ffmpeg = FFMpeg::create();
		$audio = $ffmpeg->open($audioFile->getPathname());

		$audio->filters()->clip(\FFMpeg\Coordinate\TimeCode::fromString($startTime), \FFMpeg\Coordinate\TimeCode::fromString($duration));

		$audio->save(new \FFMpeg\Format\Audio\Mp3(), $outputPath);

		return response()->download($outputPath)->deleteFileAfterSend(true);
	}

	public function testFFmpeg()
	{
		try {
			// Open the example.mp3 file from the local disk
			$ffmpeg = FFMpeg::fromDisk('local')
			                ->open('example.mp3');

			// Define the start time and duration for cropping
			$startTime = TimeCode::fromSeconds(10);  // e.g., 10 seconds
			$duration = TimeCode::fromSeconds(30);   // e.g., 30 seconds

			// Apply the crop filter
			$ffmpeg->filters()
			       ->clip($startTime, $duration);

			// Save the cropped audio to a new file
			$outputPath = storage_path('app/public/cropped_audio.mp3');
			$ffmpeg->export()
			       ->toDisk('local')
			       ->inFormat(new \FFMpeg\Format\Audio\Mp3)
			       ->save($outputPath);

			// Return a response to download the cropped audio file
			return response()->download($outputPath)->deleteFileAfterSend(true);
		} catch (\Exception $e) {
			// If an error occurs, return an error response
			return response()->json(['error' => $e->getMessage()], 500);
		}
	}


}
