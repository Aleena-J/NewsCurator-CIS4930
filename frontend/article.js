var selectedRating = 0;
var currentArticleId = "";

$(document).on("click", ".article-rate-btn", function() {
  var articleTitle = $(this).data("title");
  currentArticleId = $(this).data("id");
  selectedRating = 0;

  $(".star").removeClass("selected hovered");
  $("#star-label").text("Select a rating");
  $("#rating-submit-btn").prop("disabled", true);

  $("#rating-popup-title").text(articleTitle);

  $("#rating-popup").fadeIn(150);
});

$(document).on("mouseenter", ".star", function() {
  var val = parseInt($(this).data("value"));
  $(".star").each(function() {
      if (parseInt($(this).data("value")) <= val) {
          $(this).addClass("hovered");
      } else {
          $(this).removeClass("hovered");
      }
  });
});


$(document).on("mouseleave", ".star-row", function() {
  $(".star").removeClass("hovered");
});


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


$("#rating-submit-btn").on("click", function () {
    var comment = $("#rating-comment").val().trim();

    if (!selectedRating) {
        alert("Please select a rating.");
        return;
    }

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

			addCommentToPage(response.comment);
			$("#article-total-score").html("&#9733; " + response.avg_rating + "/5");
			$("#rating-message").text(response.message);

			$("#rating-comment").val("");
			selectedRating = 0;
			$(".star").removeClass("selected hovered");
			$("#star-label").text("Select a rating");
			$("#rating-submit-btn").prop("disabled", true);

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


$("#rating-cancel-btn").on("click", function() {
  $("#rating-popup").fadeOut(150);
});


$("#rating-popup").on("click", function(e) {
  if ($(e.target).is("#rating-popup")) {
      $("#rating-popup").fadeOut(150);
  }
});

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

    $("#comments-list .comment-card[data-user-id='" + comment.user_id + "']").remove();

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

function escapeHtml(text) {
    return $("<div>").text(text).html();
}