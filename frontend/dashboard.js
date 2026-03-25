var PROXY = "../backend/api/news_proxy.php";
var currentQuery = "performance_score:5";
var nextPageUrl = null;

function loadNews(query, append) {
    // Append is for paginations, appends new articles once load more is pressed
    var url = PROXY + "?q=" + encodeURIComponent(query);

    if (append && nextPageUrl != null) {
        url = PROXY + "?next=" + encodeURIComponent(nextPageUrl);
    } else {
        url = PROXY + "?q=" + encodeURIComponent(query);
    }

    if (!append) {
        $("#news-grid").html("<p class='loading-msg'>Loading articles...</p>");
        $("#load-more-btn").hide();
    }

    $.ajax({
        url: url,
        method: "GET",
        success: function(data) {
            var posts = data.posts;
            nextPageUrl = data.next;

            $("#results-info").text(data.totalResults + " articles found");

            if (posts.length == 0) {
                $("#news-grid").html("<p class='empty-msg'>No articles found. Try a different search.</p>");
                return;
            }

            var html = "";
            for (var i = 0; i < posts.length; i++) {
                html += makeCard(posts[i]);
            }

            if (append) {
                $("#news-grid").append(html);
            } else {
                $("#news-grid").html(html);
            }

            if (nextPageUrl != null) {
                $("#load-more-btn").show();
            } else {
                $("#load-more-btn").hide();
            }
        },
        error: function() {
            $("#news-grid").html("<p class='error-msg'>Could not load articles. Check that API key is set.</p>");
        }
    });
}

function makeCard(post) {
    var title   = post.title || "No title";
    var url     = post.url   || "URL Unavailable";
    var source  = (post.thread && post.thread.site_full) ? post.thread.site_full : "";
    var image   = (post.thread && post.thread.main_image) ? post.thread.main_image : "";

    var imageHtml = "";
    if (image != "") {
        imageHtml = "<img src='" + image + "' alt='article image' onerror=\"this.outerHTML='<div class=no-image>No image available</div>'\">";
    } else {
        imageHtml = "<div class='no-image'>No image available</div>";
    }

    var card = "<div class='news-card'>";
    card +=     imageHtml;
    card +=     "<div class='card-rating-badge'>&#9733; 0/5</div>";
    card +=     "<div class='card-body'>";
    if (source != "") {
        card += "<span class='card-source'>" + source + "</span>";
    }
    card +=     "<a class='card-title' href='" + url + "' target='_blank'>" + title + "</a>";
    card +=     "<button class='card-rate-btn' data-title='" + title.replace(/'/g, "&#39;") + "'>Rate this article</button>";
    card +=     "<a class='card-link' href='" + url + "' target='_blank'>Read full article &rarr;</a>";
    card +=     "</div>";
    card += "</div>";

    return card;
}

var today = new Date();
$("#today-date").text(today.toDateString());

$(document).ready(function() {
    loadNews("performance_score:5", false);
});


$("#search-btn").on("click", function() {
    var query = $("#search-input").val().trim();
    if (query == "") {
        return;
    }

    currentQuery = query;
    nextPageUrl = null;

    $(".topic-tab").removeClass("active");

    loadNews(query, false);
});

$("#search-input").on("keypress", function(e) {
    if (e.which == 13) { //enter key
        $("#search-btn").click();
    }
});

$("#load-more-btn").on("click", function() {
    loadNews(currentQuery, true);
});

$(".topic-tab").on("click", function() {
    $(".topic-tab").removeClass("active");
    $(this).addClass("active");
 
    var query = $(this).data("query");
    currentQuery = query;
    nextPageUrl = null;
    loadNews(query, false);
});

var selectedRating = 0;
 
$(document).on("click", ".card-rate-btn", function() {
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