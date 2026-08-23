(function ($) {
	var frame;

	function updateVideoPreview(id, url) {
		var $preview = $('#event-video-preview');
		var $input = $('#event_video');
		var $remove = $('#event-video-remove');

		$input.val(id || '');

		if (!id || !url) {
			$preview.empty();
			$remove.hide();
			return;
		}

		$preview.html(
			'<p><a href="' + url + '" target="_blank" rel="noopener">' +
				eventsAdmin.viewVideoLabel +
				'</a></p>'
		);
		$remove.show();
	}

	$('#event-video-select').on('click', function (e) {
		e.preventDefault();

		if (frame) {
			frame.open();
			return;
		}

		frame = wp.media({
			title: eventsAdmin.selectVideoTitle,
			button: { text: eventsAdmin.useVideoLabel },
			library: { type: 'video' },
			multiple: false,
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			updateVideoPreview(attachment.id, attachment.url);
		});

		frame.open();
	});

	$(document).on('click', '#event-video-remove', function (e) {
		e.preventDefault();
		updateVideoPreview(0, '');
	});

	function toggleLocationFields() {
		var isPhysical = $('#event_type').val() === 'physical';
		$('#event-location-fields').toggle(isPhysical);
	}

	function initAutocomplete() {
		if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
			return;
		}

		var input = document.getElementById('event_location');
		if (!input) {
			return;
		}

		var autocomplete = new google.maps.places.Autocomplete(input);

		autocomplete.addListener('place_changed', function () {
			var place = autocomplete.getPlace();
			if (!place.geometry) {
				return;
			}

			$('#event_latitude').val(place.geometry.location.lat());
			$('#event_longitude').val(place.geometry.location.lng());
			$('#event_place_id').val(place.place_id || '');
		});
	}

	$(function () {
		toggleLocationFields();
		$('#event_type').on('change', toggleLocationFields);
		initAutocomplete();
	});
})(jQuery);
