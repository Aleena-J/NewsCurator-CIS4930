var selectedRating = 0;

$(document).on("click", ".article-rate-btn", function() {
  var articleTitle = $(this).data("title");
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

//Submit button — placeholder
$("#rating-submit-btn").on("click", function() {
  // TODO: send selectedRating and article info to backend to save in DB
  alert("You rated this article " + selectedRating + " out of 5 stars");
  $("#rating-popup").fadeOut(150);
});


$("#rating-cancel-btn").on("click", function() {
  $("#rating-popup").fadeOut(150);
});


$("#rating-popup").on("click", function(e) {
  if ($(e.target).is("#rating-popup")) {
      $("#rating-popup").fadeOut(150);
  }
});