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
        type: "POST",
        data: {
            article_id: currentArticleId,
            rating: selectedRating,
            comment: comment
        },

        beforeSend: function () {
            $("#rating-submit-btn")
                .text("Submitting...")
                .prop("disabled", true);
        },

        success: function (response) {

            $("#rating-message")
                .removeClass("error")
                .addClass("success")
                .text("Review saved!")
                .fadeIn(200);

            $("#rating-submit-btn").text("Submit");

            setTimeout(function () {
                $("#rating-popup").fadeOut(150);
                $("#rating-message").hide();

                $("#rating-comment").val("");
                selectedRating = 0;
                currentArticleId = "";
                $(".star").removeClass("selected hovered");
                $("#star-label").text("Select a rating");
                $("#rating-submit-btn").prop("disabled", true);

            }, 1200);
        },

        error: function () {
            $("#rating-message")
                .removeClass("success")
                .addClass("error")
                .text("Something went wrong.")
                .fadeIn(200);

            $("#rating-submit-btn")
                .text("Submit")
                .prop("disabled", false);
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