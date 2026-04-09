var PROXY = "../backend/api/news_proxy.php";
var currentQuery = "performance_score:5";
var nextPageUrl = null;

function loadNews(query, append) {
    if (query.length > 100) {
        $("#results-info").text(
            "This query is too long for the API (" +
            query.length +
            " characters). Reduce selected filters in your profile."
        );
        $("#news-grid").empty();
        $("#load-more-btn").hide();
        return;
    }

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

function articlePageUrl(post) {
    var p = new URLSearchParams();
    p.set("url", post.url || "");
    p.set("title", post.title || "");
    p.set(
        "publisher",
        post.thread && post.thread.site_full ? post.thread.site_full : ""
    );
    p.set(
        "country",
        post.thread && post.thread.country ? post.thread.country : ""
    );
    p.set("date", post.published || (post.thread && post.thread.published) || post.crawled || "");
    p.set("language", post.language || "");
    return "article.php?" + p.toString();
}
 
function snippetHtml(post) {
    if (post.highlightText) {
        return post.highlightText;
    }
    var plain = post.text || post.summary || "";
    return plain ? $("<div>").text(plain).html() : "";
}

function makeCard(post) {
    var title   = post.title || "No title";
    var url     = post.url   || "URL Unavailable";
    var source  = (post.thread && post.thread.site) ? post.thread.site : "";
    var image   = (post.thread && post.thread.main_image) ? post.thread.main_image : "";
    var snippet = snippetHtml(post);
    var articlePgeUrl  = articlePageUrl(post);

    var imageHtml = "";
    if (image != "") {
        imageHtml = "<img src='" + image + "' alt='article image' onerror=\"this.outerHTML='<div class=no-image>No image available</div>'\">";
    } else {
        imageHtml = "<div class='no-image'>No image available</div>";
    }

    var card = "<div class='news-card'>";
    card +=     "<a href='" + articlePgeUrl + "'>" + imageHtml + "</a>";
    card +=     "<div class='card-rating-badge'>&#9733; 0/5</div>";
    card +=     "<div class='card-body'>";
    if (source != "") {
        card += "<span class='card-source'>" + source + "</span>";
    }
    card +=     "<a class='card-title' href='" + articlePgeUrl + "'>" + title + "</a>";
    if (snippet != "") {
        card += "<p class='card-snippet'>" + snippet + "</p>";
    }
    card += "<button class='card-rate-btn' data-title='" + title.replace(/'/g, "&#39;") + "' data-id='" + url.replace(/'/g, "&#39;") + "'>Rate this article</button>";
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

    if (query.length > 100) {
        $("#results-info").text(
            "Queries can be at most 100 characters. Yours is " + query.length + " characters. Use shorter keywords."
        );
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
var currentArticleId = "";
 
$(document).on("click", ".card-rate-btn", function() {
    var articleTitle = $(this).data("title");
    currentArticleId = $(this).data("id");
    selectedRating = 0;

    $(".star").removeClass("selected hovered");
    $("#star-label").text("Select a rating");
    $("#rating-submit-btn").prop("disabled", true);
    $("#rating-comment").val("");
    $("#rating-message").hide().text("").removeClass("success error");

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

	if (!currentArticleId) {
    $("#rating-message")
        .removeClass("success")
        .addClass("error")
        .text("Missing article ID.")
        .fadeIn(200);
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
		response = String(response).trim();

		if (response !== "Saved!") {
			$("#rating-message")
				.removeClass("success")
				.addClass("error")
				.text(response)
				.fadeIn(200);

			$("#rating-submit-btn")
				.text("Submit")
				.prop("disabled", false);

			return;
		}

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