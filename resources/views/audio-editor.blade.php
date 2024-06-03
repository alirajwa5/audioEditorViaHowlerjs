<!-- resources/views/audio_editor.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audio Editor</title>
</head>
<body>
<h1>Audio Editor</h1>

<h2>Crop Audio</h2>
<input type="file" id="crop-input" accept="audio/*">
<input type="number" id="start-time" placeholder="Start Time (seconds)">
<input type="number" id="end-time" placeholder="End Time (seconds)">
<button onclick="cropAudio()">Crop</button>
<audio id="cropped-audio" controls></audio>

<h2>Merge Audio</h2>
<input type="file" id="merge-input1" accept="audio/*">
<input type="file" id="merge-input2" accept="audio/*">
<button onclick="mergeAudio()">Merge</button>
<audio id="merged-audio" controls></audio>

<h2>Add Background Music</h2>
<input type="file" id="main-audio" accept="audio/*">
<input type="file" id="bg-music" accept="audio/*">
<button onclick="addBackgroundMusic()">Add Background Music</button>
<audio id="combined-audio" controls></audio>

<script src="https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.3/howler.min.js"></script>
<script>
	async function cropAudio() {
		const input = document.getElementById('crop-input');
		const startTime = document.getElementById('start-time').value;
		const endTime = document.getElementById('end-time').value;
		const audioElement = document.getElementById('cropped-audio');

		if (input.files.length === 0) {
			alert('Please select an audio file.');
			return;
		}

		const file = input.files[0];
		const arrayBuffer = await file.arrayBuffer();
		const audioContext = new (window.AudioContext || window.webkitAudioContext)();
		const audioBuffer = await audioContext.decodeAudioData(arrayBuffer);

		const duration = endTime - startTime;
		const croppedBuffer = audioContext.createBuffer(
			audioBuffer.numberOfChannels,
			duration * audioBuffer.sampleRate,
			audioBuffer.sampleRate
		);

		for (let i = 0; i < audioBuffer.numberOfChannels; i++) {
			croppedBuffer.copyToChannel(
				audioBuffer.getChannelData(i).slice(startTime * audioBuffer.sampleRate, endTime * audioBuffer.sampleRate),
				i
			);
		}

		const croppedBlob = bufferToWave(croppedBuffer, croppedBuffer.length);
		audioElement.src = URL.createObjectURL(croppedBlob);
		audioElement.play();
	}

	async function mergeAudio() {
		const input1 = document.getElementById('merge-input1');
		const input2 = document.getElementById('merge-input2');
		const audioElement = document.getElementById('merged-audio');

		if (input1.files.length === 0 || input2.files.length === 0) {
			alert('Please select both audio files.');
			return;
		}

		const file1 = input1.files[0];
		const file2 = input2.files[0];

		const arrayBuffer1 = await file1.arrayBuffer();
		const arrayBuffer2 = await file2.arrayBuffer();

		const audioContext = new (window.AudioContext || window.webkitAudioContext)();
		const audioBuffer1 = await audioContext.decodeAudioData(arrayBuffer1);
		const audioBuffer2 = await audioContext.decodeAudioData(arrayBuffer2);

		const numberOfChannels = Math.max(audioBuffer1.numberOfChannels, audioBuffer2.numberOfChannels);
		const mergedDuration = audioBuffer1.duration + audioBuffer2.duration;
		const mergedBuffer = audioContext.createBuffer(
			numberOfChannels,
			mergedDuration * audioBuffer1.sampleRate,
			audioBuffer1.sampleRate
		);

		for (let i = 0; i < numberOfChannels; i++) {
			const channelData = mergedBuffer.getChannelData(i);
			if (audioBuffer1.numberOfChannels > i) {
				channelData.set(audioBuffer1.getChannelData(i), 0);
			}
			if (audioBuffer2.numberOfChannels > i) {
				channelData.set(audioBuffer2.getChannelData(i), audioBuffer1.length);
			}
		}

		const mergedBlob = bufferToWave(mergedBuffer, mergedBuffer.length);
		audioElement.src = URL.createObjectURL(mergedBlob);
		audioElement.play();
	}

	async function addBackgroundMusic() {
		const mainAudioInput = document.getElementById('main-audio');
		const bgMusicInput = document.getElementById('bg-music');
		const audioElement = document.getElementById('combined-audio');

		if (mainAudioInput.files.length === 0 || bgMusicInput.files.length === 0) {
			alert('Please select both main audio and background music files.');
			return;
		}

		const mainAudioFile = mainAudioInput.files[0];
		const bgMusicFile = bgMusicInput.files[0];

		const mainArrayBuffer = await mainAudioFile.arrayBuffer();
		const bgArrayBuffer = await bgMusicFile.arrayBuffer();

		const audioContext = new (window.AudioContext || window.webkitAudioContext)();
		const mainAudioBuffer = await audioContext.decodeAudioData(mainArrayBuffer);
		const bgAudioBuffer = await audioContext.decodeAudioData(bgArrayBuffer);

		const numberOfChannels = Math.max(mainAudioBuffer.numberOfChannels, bgAudioBuffer.numberOfChannels);
		const outputDuration = Math.max(mainAudioBuffer.duration, bgAudioBuffer.duration);
		const outputBuffer = audioContext.createBuffer(
			numberOfChannels,
			outputDuration * mainAudioBuffer.sampleRate,
			mainAudioBuffer.sampleRate
		);

		for (let i = 0; i < numberOfChannels; i++) {
			const outputData = outputBuffer.getChannelData(i);
			if (mainAudioBuffer.numberOfChannels > i) {
				outputData.set(mainAudioBuffer.getChannelData(i), 0);
			}
			if (bgAudioBuffer.numberOfChannels > i) {
				const bgData = bgAudioBuffer.getChannelData(i);
				for (let j = 0; j < bgData.length; j++) {
					outputData[j] += bgData[j] * 0.5; // Adjust volume of background music
				}
			}
		}

		const outputBlob = bufferToWave(outputBuffer, outputBuffer.length);
		audioElement.src = URL.createObjectURL(outputBlob);
		audioElement.play();
	}

	function bufferToWave(abuffer, len) {
		let numOfChan = abuffer.numberOfChannels,
		    length = len * numOfChan * 2 + 44,
		    buffer = new ArrayBuffer(length),
		    view = new DataView(buffer),
		    channels = [], i, sample,
		    offset = 0,
		    pos = 0;

		// write WAVE header
		setUint32(0x46464952);                         // "RIFF"
		setUint32(length - 8);                         // file length - 8
		setUint32(0x45564157);                         // "WAVE"

		setUint32(0x20746d66);                         // "fmt " chunk
		setUint32(16);                                 // length = 16
		setUint16(1);                                  // PCM (uncompressed)
		setUint16(numOfChan);
		setUint32(abuffer.sampleRate);
		setUint32(abuffer.sampleRate * 2 * numOfChan); // avg. bytes/sec
		setUint16(numOfChan * 2);                      // block-align
		setUint16(16);                                 // 16-bit (hardcoded in this demo)

		setUint32(0x61746164);                         // "data" - chunk
		setUint32(length - pos - 4);                   // chunk length

		// write interleaved data
		for (i = 0; i < abuffer.numberOfChannels; i++)
			channels.push(abuffer.getChannelData(i));

		while (pos < length) {
			for (i = 0; i < numOfChan; i++) {             // interleave channels
				sample = Math.max(-1, Math.min(1, channels[i][offset])); // clamp
				sample = (0.5 + sample * 32767) | 0;        // scale to 16-bit signed int
				view.setInt16(pos, sample, true);          // write 16-bit sample
				pos += 2;
			}
			offset++                                     // next source sample
		}

		return new Blob([buffer], { type: "audio/wav" });

		function setUint16(data) {
			view.setUint16(pos, data, true);
			pos += 2;
		}

		function setUint32(data) {
			view.setUint32(pos, data, true);
			pos += 4;
		}
	}
</script>
</body>
</html>
