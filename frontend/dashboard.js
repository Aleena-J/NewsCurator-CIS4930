var PROXY = "../backend/api/news_proxy.php";
var CATEGORY_FILTER = {
    sports: "sport",
    sport: "sport",
    politics: "politics",
    environment: "environment",
    health: "health",
    education: "education",
    science: '"Science and Technology"',
    technology: '"Science and Technology"',
    business: '"Economy, Business and Finance"',
    economy: '"Economy, Business and Finance"',
    crime: '"Crime, Law and Justice"',
    human_interest: '"Human Interest"',
    war: '"War, Conflict and Unrest"',
};
var currentSearchParams = { q: "performance_score:>=3 domain_rank:<2000", country: "", language: "", category: "" };
var nextPageUrl = null;

var currentTopicCategory = "";
var currentTopicBaseQuery = "performance_score:>=3 domain_rank:<2000";

function dashboardParamsFromTopic(topicName, prebuiltQuery) {
    if (prebuiltQuery && prebuiltQuery.trim() !== "") {
        return {
            q: prebuiltQuery,
            country: "",
            language: "",
            category: ""
        };
    }

    var topic = (topicName || "").toString().trim().toLowerCase();
    if (topic === "") {
        return {
            q: "performance_score:>=3 domain_rank:<2000",
            country: "",
            language: "",
            category: ""
        };
    }

    var cat = CATEGORY_FILTER[topic];
    if (cat) {
        return { q: "domain_rank:<2000 category:" + cat, country: "", language: "", category: "" };
    }
    return { q: 'domain_rank:<2000 text:"' + topicName + '"', country: "", language: "", category: "" };
}

function buildLocalePrefix() {
    var parts = [];
    if (typeof userCountries !== "undefined") {
        for (var i = 0; i < userCountries.length; i++) {
            var c = (userCountries[i] || "").toString().trim().toLowerCase();
            if (c !== "") { parts.push("country:" + c); }
        }
    }
    if (typeof userLanguages !== "undefined") {
        for (var j = 0; j < userLanguages.length; j++) {
            var l = (userLanguages[j] || "").toString().trim().toLowerCase();
            if (l !== "") { parts.push("language:" + l); }
        }
    }
    return parts.join(" ");
}

function buildProxyUrl(params) {
    var p = new URLSearchParams();
    if (params.q) {
        p.set("q", params.q);
    }
    if (params.country) {
        p.set("country", params.country);
    }
    if (params.language) {
        p.set("language", params.language);
    }
    if (params.category) {
        p.set("category", params.category);
    }
    return PROXY + "?" + p.toString();
}

function loadNews(params, append) {
    if (params.q && params.q.length > 100) {
        $("#results-info").text(
            "This query is too long for the API (" +
            params.q.length +
            " characters). Reduce selected filters in your profile."
        );
        $("#news-grid").empty();
        $("#load-more-btn").hide();
        return;
    }

    // Append is for paginations, appends new articles once load more is pressed
    var url = buildProxyUrl(params);

    if (append && nextPageUrl != null) {
        url = PROXY + "?next=" + encodeURIComponent(nextPageUrl);
    } else {
        url = buildProxyUrl(params);
    }

    //debug statements
    console.group("loadNews()");
    console.log("params :", JSON.stringify(params));
    console.log("url    :", url);
    console.groupEnd();

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
			
			updateDashboardRatings();

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

function updateDashboardRatings() {
    $(".card-rating-badge").each(function () {
        let badge = $(this);
        let url = badge.data("url");

        if (!url) return;

        $.ajax({
            url: "get_rating.php",
            method: "GET",
            dataType: "json",
            data: { article_id: url },
            success: function (res) {
                let avg = parseFloat(res.avg || 0);
				let display;
				if (avg === 5 || avg === 0) {
					display = String(avg);
				} else {
					display = avg.toFixed(1);
				}

                badge.html("&#9733; " + display + "/5");
            }
        });
    });
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
    card += "<div class='card-rating-badge' data-url='" + url.replace(/'/g, "&#39;") + "'>&#9733; 0/5</div>";
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
    var popularQuery = $(".topic-tab.active").data("query");
    if (popularQuery && popularQuery.trim() !== "") {
        currentSearchParams.q = popularQuery;
    } else {
        // add country and language preference to popular search
        var locale = buildLocalePrefix();
        currentSearchParams.q = "performance_score:>=3 domain_rank:<2000"
            + (locale !== "" ? " " + locale : "");
    }
    //debug
    console.log("Dashboard init query:", currentSearchParams.q);
    loadNews(currentSearchParams, false);
});


$("#search-btn").on("click", function() {
    var keywords = $("#search-input").val().trim();
    if (keywords === "") {
        return;
    }

    var q = 'text:"' + keywords + '"';
    if (q.length > 100) {
        $("#results-info").text(
            "Max keyword length is 93 characters. Yours is " + keywords.length + "."
        );
        return;
    }

    currentSearchParams = {
        q: q,
        country: "",
        language: "",
        category: "",
    };
    nextPageUrl = null;

    $(".topic-tab").removeClass("active");
    $("#source-tabs-wrap").hide();
    $(".source-tab").removeClass("active");

    loadNews(currentSearchParams, false);
});

$("#search-input").on("keypress", function(e) {
    if (e.which == 13) { //enter key
        $("#search-btn").click();
    }
});

$("#load-more-btn").on("click", function() {
    loadNews(currentSearchParams, true);
});

$(".topic-tab").on("click", function() {
    $(".topic-tab").removeClass("active");
    $(this).addClass("active");
 
    var topic = $(this).data("topic");
    var prebuilt   = $(this).data("query");
    var isPopular  = $(this).data("is-popular") == "1";
    $(".source-tab").removeClass("active");
    if (isPopular) {
        $("#source-tabs-wrap").hide();
        currentTopicCategory = "";
    } else {
        $("#source-tabs-wrap").show();
        var topicKey = (topic || "").toString().trim().toLowerCase();
        currentTopicCategory = CATEGORY_FILTER[topicKey] || "";
    }
    currentSearchParams = dashboardParamsFromTopic(topic, prebuilt);
    currentTopicBaseQuery  = currentSearchParams.q;
    nextPageUrl = null;
    //debug
    console.log("Topic tab clicked:", topic || "Popular", "| query:", currentSearchParams.q);
    loadNews(currentSearchParams, false);
});

$(".source-tab").on("click", function() {
    $(".source-tab").removeClass("active");
    $(this).addClass("active");

    var sourceKey = $(this).data("source");

    // HARD CODED DOMAINS - SWITCH TO USER PREF
    var domainMap = {
        "cnn":       "cnn.com",
        "bloomberg": "bloomberg.com"
    };

    var domain = domainMap[sourceKey];
    if (!domain) {
        console.warn("No domain mapping for source key:", sourceKey);
        return;
    }

    var cleanQ;
    if (currentTopicCategory !== "") {
        cleanQ = "category:" + currentTopicCategory + " site:" + domain;
    } else {
        cleanQ = "site:" + domain;
    }

    currentSearchParams = {
        q: cleanQ,
        country:  "",
        language: "",
        category: ""
    };
    nextPageUrl = null;

    console.log("Source tab clicked:", sourceKey, "| query:", currentSearchParams.q);

    loadNews(currentSearchParams, false);
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
			comment_text: comment
		},
		dataType: "json",

        beforeSend: function () {
            $("#rating-submit-btn")
                .text("Submitting...")
                .prop("disabled", true);
        },

      success: function (response) {
		if (!response.success) {
			$("#rating-message")
				.removeClass("success")
				.addClass("error")
				.text(response.message)
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

		$(".card-rating-badge").each(function () {
			if ($(this).data("url") === currentArticleId) {
				$(this).html("&#9733; " + response.avg_rating + "/5");
			}
		});

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