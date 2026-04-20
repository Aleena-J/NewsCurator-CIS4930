// initial variables storing user currently selected star rating and article id
var selectedRating = 0;
var currentArticleId = "";

// When the user clicks on the "Rate this article" button.
$(document).on("click", ".article-rate-btn", function() {
  var articleTitle = $(this).data("title"); // article title
  currentArticleId = $(this).data("id"); // article ID (URL)
  selectedRating = 0; // selected rating
  
  $(".star").removeClass("selected hovered"); // clear previously selected or hovered stars
  $("#star-label").text("Select a rating");
  $("#rating-submit-btn").prop("disabled", true);

  $("#rating-popup-title").text(articleTitle);
	
	//show the rating popup
  $("#rating-popup").fadeIn(150);
});

// when hovering over a star
$(document).on("mouseenter", ".star", function() {
  var val = parseInt($(this).data("value")); // value of star
  
  // highlights all stars up to the one chosen
  $(".star").each(function() {
      if (parseInt($(this).data("value")) <= val) {
          $(this).addClass("hovered");
      } else {
          $(this).removeClass("hovered");
      }
  });
});

// when mouse leaves the whole star row
$(document).on("mouseleave", ".star-row", function() {
  $(".star").removeClass("hovered");
});

// When user clicks a star to chose a rating
$(document).on("click", ".star", function() {
  selectedRating = parseInt($(this).data("value"));
  $(".star").each(function() {
      if (parseInt($(this).data("value")) <= selectedRating) {
          $(this).addClass("selected");
      } else {
          $(this).removeClass("selected");
      }
  });
  $("#star-label").text(selectedRating + " out of 5 stars");
  $("#rating-submit-btn").prop("disabled", false);
});

// when the user clicks the submit button
$("#rating-submit-btn").on("click", function () {
    var comment = $("#rating-comment").val().trim();

	// checks rating was selected
    if (!selectedRating) {
        alert("Please select a rating.");
        return;
    }

	// send rating and comment to backend using AJAX
	$.ajax({
		url: "submit_rating.php",
		method: "POST",
		dataType: "json",
		data: {
			article_id: currentArticleId,
			rating: selectedRating,
			comment_text: $("#rating-comment").val().trim()
		},
		success: function (response) {
			if (!response.success) {
				$("#rating-message").text(response.message);
				return;
			}

			addCommentToPage(response.comment); // Add or update the user's comment on page
			
			$("#article-total-score").html("&#9733; " + response.avg_rating + "/5");
			$("#rating-message").text(response.message);

			$("#rating-comment").val("");
			selectedRating = 0;
			$(".star").removeClass("selected hovered");
			$("#star-label").text("Select a rating");
			$("#rating-submit-btn").prop("disabled", true);
			
			//close popup after short delay
			setTimeout(() => {
				$("#rating-popup").fadeOut(150);
				$("#rating-message").text("");
			}, 800);
		},
		error: function () {
			$("#rating-message").text("Something went wrong.");
		}
	});
});


// When user clicks cancel button, close popup
$("#rating-cancel-btn").on("click", function() {
  $("#rating-popup").fadeOut(150);
});

// Close popup if user clicks outside the popup box
$("#rating-popup").on("click", function(e) {
  if ($(e.target).is("#rating-popup")) {
      $("#rating-popup").fadeOut(150);
  }
});

// Adds a new comment to the page or replaces existing one from same user
function addCommentToPage(comment) {
    $("#no-comments-msg").remove();

    var safeUsername = escapeHtml(comment.username || "User");
    var safeCreatedAt = escapeHtml(comment.created_at || "");
    var safeCommentText = comment.comment_text
        ? escapeHtml(comment.comment_text).replace(/\n/g, "<br>")
        : "<em>No written comment provided.</em>";

    var rating = parseInt(comment.rating) || 0;
    var filledStars = "★".repeat(rating);
    var emptyStars = "☆".repeat(5 - rating);

	// Remove previous comment from same user so it does not duplicate
    $("#comments-list .comment-card[data-user-id='" + comment.user_id + "']").remove();

	// Build HTML for the new/updated comment card
    var commentHtml = `
        <div class="comment-card" data-user-id="${comment.user_id}">
            <div class="comment-header">
                <span class="comment-username">${safeUsername}</span>
                <span class="comment-time">${safeCreatedAt}</span>
            </div>

            <div class="comment-rating">
                ${filledStars}${emptyStars}
                <span class="comment-rating-number">${rating}/5</span>
            </div>

            <p class="comment-text">${safeCommentText}</p>
        </div>
    `;

    $("#comments-list").show().prepend(commentHtml);
}

// Escapes text so special HTML characters are not interpreted as code
function escapeHtml(text) {
    return $("<div>").text(text).html();
}