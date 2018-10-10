<?php
	header( "content-type:application/json" );

	/**
	 * Buzz the Bee ★
	 *
	 * @author	Alexander Demchak (Xhynk)
	 * @link	https://www.localrepmgr.net/slack-api/slash-reviewengine.php
	 * @version	3.0
	 * @api		http://rm2.thirdrivermarketing.com/api-v2-integration/
	 *
	 * To activate Buzz the Bee ★, use on of the following commands:
	 *
	 * @example /re [engine.domain.com]
	 * @example /buzz [engine.domain.com]
	 *
	 * @internal { Buzz the Bee ★ will fetch some information for the Review
	 *	Engine URL that is provided, using the Review Engine v2 API. }
	*/

	// Find out what reviews to pull and where to pull them from
	$review_engine_url	= 'http://'. str_replace( array( 'http:// ', 'https:// ', 'http:// www.', 'https:// www.' ), '', $_GET['text'] ); // We need to know where to get the information, and strip the HTTP because it's an invalid directive.

	// Now that we have our reviews, lets decode them so we can play with them!
	$json_response		= @file_get_contents( $review_engine_url.'/reviews-api-v2/?query_v2=true&reviews_per_page=1&user=thirdrivermarketing&key=cead.6644bbeabb' ); // Get our JSON "Engine Data" response from the site. @ to supress errors if undefined or invalid.
	$response_object	= json_decode( $json_response ); // Turn that response into an object we can work with

	// Make the object format easier to read
	$data		= $response_object->data[0];
	$reviews	= $response_object->reviews[0];

	// Let's make sure the API Call is valid, and then do some stuff
	if( $data->status == 'good' ){ // API Call was successful, it's a Review Engine!
		if( $data->message == 'token:valid::accepted' ){ // Make sure credentials are 'aight.

			$count	= $data->total_reviews; // Total Reviews
			$latest	= $reviews->review_meta->review_date->date; // Date of last review

			foreach( array( 'one_star_reviews', 'two_star_reviews', 'three_star_reviews', 'four_star_reviews', 'five_star_reviews' ) as $var ){
				${$var.'_display'}	= $data->{$var}; // Make the value displayable
				${$var.'_percent'}	= round( ( ${$var.'_display'} / $count ) * 100 ); // Find out what percentage of reviews each rating is.
			}

			// Let's see how active they are
			$current_time	= strtotime( "now" );
			$latest_time	= strtotime( $latest );

			// Break down the time since last review
			$unix_time		= $current_time - $latest_time;
			$time_in_days	= $unix_time / 86400;
			$_s				= ( $time_in_days > 1 ) ? 's' : ''; // Need to see if we use "Day" or "Days"

			// Let's have a little fun with how long it's been since a review's been found
			$finger_wag		= $unix_time < 604800 ? 'Less than a week! Nice!' :
								( $unix_time < 2629743 ? 'Hmm, time for new reviews!' :
								 	( $unix_time < 15778463 ? 'Getting pretty stagnant...' :
										( $unix_time > 15778463 && $unix_time < 31556926 ? 'There\'s a serious lack of new reviews.' :
									 		( $unix_time > 31556926 ? 'Over a year.. *tsk *tsk...' : '' ) ) ) );

			// Slack API Response has to be an array, mostly to determine how to respond
			// NOTE: This has to be formated this way to space it properly in the bot response
		 	$array[] = array(
						'response_type' => 'in_channel',
						'text' => "Bzzzz! I found some info for: *" . $_GET['text'] ."*!\r\n
	_Aggregate_: *". $data->aggregate ."★* _based on_ *". $count ." Reviews*

		★★★★★: *". $five_star_reviews_display ."* ($five_star_reviews_percent%)
		★★★★☆: *". $four_star_reviews_display ."* ($four_star_reviews_percent%)
		★★★☆☆: *". $three_star_reviews_display ."* ($three_star_reviews_percent%)
		★★☆☆☆: *". $two_star_reviews_display ."* ($two_star_reviews_percent%)
		★☆☆☆☆: *". $one_star_reviews_display ."* ($one_star_reviews_percent%)

	_Latest Review_: *". $latest ."* - That's about *". round( $time_in_days ) ."* day$_s ago.
		- ". $finger_wag
			);

			echo substr( json_encode( $array ), 1, -1 ); // We have to trim the string a little for some reason, slack's not parsing the resposne correctly
		} else {
			// It's a good URL, but bad credentials
			echo "Bzz....sigh...zz... I didn't find anything for the Review Engine \"*". $_GET['text'] ."*\" :(\r\nDon't worry, nobody else can see that I failed - just you.";
		}
	} else {
		// This probably isn't a review engine
		echo "Bzzz..zz... I didn't find anything for \"*". $_GET['text'] ."*\"\r\nAre you sure it's a valid Review Engine URL?";
	}
?>
