<?php
YouTubeDataAPIv3("Teshtung", "Teshtung Description", "./assets/videosfun.mp4", "27");
function YouTubeDataAPIv3($videoTitle, $videoDescription, $videoPath, $videoCategory)
{
    /**
     * Sample PHP code for youtube.channels.list
     * See instructions for running these code samples locally:
     * https://developers.google.com/explorer-help/guides/code_samples#php
     */

    if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
        throw new Exception(sprintf('Please run "composer require google/apiclient:~2.0" in "%s"', __DIR__));
    }
    require_once __DIR__ . '/vendor/autoload.php';

    $tokens = file_get_contents(__DIR__ . '/assets/tokens.json');

    try {
        $client = new Google_Client();
        $client->setApplicationName('API code samples');
        $client->setScopes([
            'https://www.googleapis.com/auth/youtube.readonly',
            'https://www.googleapis.com/auth/youtube.upload',
            'https://www.googleapis.com/auth/youtube',
            'https://www.googleapis.com/auth/youtubepartner',
        ]);

        // TODO: For this request to work, you must replace
        //       "YOUR_CLIENT_SECRET_FILE.json" with a pointer to your
        //       client_secret.json file. For more information, see
        //       https://cloud.google.com/iam/docs/creating-managing-service-account-keys
        $client->setAuthConfig(__DIR__ . '/assets/client_secret.json');
        $client->setAccessType('offline');
        $client->setAccessToken($tokens);

        if ($client->getAccessToken()) {
            //Check to see if our access token has expired. If so, get a new one and save it to tokens.json for future use.
            if ($client->isAccessTokenExpired()) {
                echo "Token was expired getting a new one .....";
                $newToken = $client->getAccessToken();
                $client->refreshToken($newToken['refresh_token']);
                file_put_contents(__DIR__ . '/assets/tokens.json', json_encode($newToken));
            }

            $youtube = new Google_Service_YouTube($client);

            // Create a snipet with title, description, tags and category id
            $snippet = new Google_Service_YouTube_VideoSnippet();
            $snippet->setTitle($videoTitle);
            $snippet->setDescription($videoDescription);
            $snippet->setCategoryId($videoCategory);
            //$snippet->setTags($videoTags);

            // Create a video status with privacy status. Options are "public", "private" and "unlisted".
            $status = new Google_Service_YouTube_VideoStatus();
            $status->setPrivacyStatus('unlisted');

            // Create a YouTube video with snippet and status
            $video = new Google_Service_YouTube_Video();
            $video->setSnippet($snippet);
            $video->setStatus($status);

            // Size of each chunk of data in bytes. Setting it higher leads faster upload (less chunks,
            // for reliable connections). Setting it lower leads better recovery (fine-grained chunks)
            $chunkSizeBytes = 1 * 1024 * 1024;

            // Setting the defer flag to true tells the client to return a request which can be called
            // with ->execute(); instead of making the API call immediately.
            $client->setDefer(true);

            // Create a request for the API's videos.insert method to create and upload the video.
            $insertRequest = $youtube->videos->insert("status,snippet", $video);

            // Create a MediaFileUpload object for resumable uploads.
            $media = new Google_Http_MediaFileUpload(
                $client,
                $insertRequest,
                'video/*',
                null,
                true,
                $chunkSizeBytes
            );
            $media->setFileSize(filesize($videoPath));

            // Read the media file and upload it chunk by chunk.
            $status = false;
            $handle = fopen($videoPath, "rb");
            while (!$status && !feof($handle)) {
                $chunk = fread($handle, $chunkSizeBytes);
                $status = $media->nextChunk($chunk);
            }

            fclose($handle);

            /**
             * Video has successfully been upload, now lets perform some cleanup functions for this video
             */
            if ($status->status['uploadStatus'] == 'uploaded') {
                ?>
            <iframe class="blue stroke rounded" style="min-width: 50%; min-height: 50%;"
                src="https://www.youtube.com/embed/<?php echo $status['id']; ?>?rel=0&amp;showinfo=0" frameborder="0"
                allow="autoplay; encrypted-media" allowfullscreen></iframe>

            <?php
} else {
                echo "Something went erong";
            }

            // If you want to make other calls after the file upload, set setDefer back to false
            $client->setDefer(true);
        } else {
            // @TODO Log error
            echo 'Problems creating the client';
        }
    } catch (Google_Service_Exception $e) {
        print "Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage();
        print "Stack trace is " . $e->getTraceAsString();
    } catch (Exception $e) {
        print "Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage();
        print "Stack trace is " . $e->getTraceAsString();
    }
}