var PROXY = "../backend/api/news_proxy_dash.php";
var CATEGORY_FILTER = {
    sports: "sport",
    sport: "sport",
    politics: "politics",
    environment: "environment",
    health: "health",
    education: "education",
    science: 'Science and Technology',
    technology: 'Science and Technology',
    business: 'Economy, Business and Finance',
    economy: 'Economy, Business and Finance',
    crime: 'Crime, Law and Justice',
    human_interest: 'Human Interest',
    war: 'War, Conflict and Unrest',
};

var currentSearchParams = {
    q: 'domain_rank:<5000 performance_score:>=3', language: '', country: '', category: ''};
var nextPageUrl = null;
var currentTopicCategory = "";



function buildProxyUrl(params) {
    var p = new URLSearchParams();
    if (params.q) {
        p.set("q", params.q);
    }
    if (params.category) {
        p.set("category", params.category);
    }

    if (params.language && params.language !== '') {
        var langs = Array.isArray(params.language)
            ? params.language
            : params.language.split(',');
        for (var i = 0; i < langs.length; i++) {
            if ((langs[i] + '').trim() !== '') {
                p.append('language[]', (langs[i] + '').trim());
            }
        }
    }

    if (params.country && params.country !== '') {
        var countries = Array.isArray(params.country)
            ? params.country
            : params.country.split(',');
        for (var j = 0; j < countries.length; j++) {
            if ((countries[j] + '').trim() !== '') {
                p.append('country[]', (countries[j] + '').trim());
            }
        }
    }
    return PROXY + "?" + p.toString();
}

function paramsFromTab($tab) {
    var raw = $tab.data('params');
    if (raw && typeof raw === 'object') {
        return {
            q: raw.q || '',
            language: raw.language || '',
            country: raw.country || '',
            category: raw.category || ''
        };
    }
    return {
        q: 'domain_rank:<5000',
        language: '', country: '', category: ''
    };
}

function loadNews(params, append) {
    // Append is for paginations, appends new articles once load more is pressed
    var url;

    if (append && nextPageUrl != null) {
        url = PROXY + '?next=' + encodeURIComponent(nextPageUrl);
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
    p.set(
        "image",
        post.thread && post.thread.main_image ? post.thread.main_image : ""
    );
	p.set("description", post.text || post.description || "");
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
    var $active = $('.topic-tab.active');
    if ($active.length) {
        currentSearchParams = paramsFromTab($active);
    }
    console.log('Dashboard init params:', JSON.stringify(currentSearchParams));
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

    currentSearchParams  = { q: q, language: '', country: '', category: '' };
    currentTopicCategory = '';
    nextPageUrl          = null;
    $('.topic-tab').removeClass('active');
    $('#source-tabs-wrap').hide();
    $('.source-tab').removeClass('active');
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
 
    var isPopular = $(this).data('is-popular') == '1';
    var topic     = ($(this).data('topic') || '').toString().trim().toLowerCase();

    $(".source-tab").removeClass("active");

    if (isPopular) {
        $("#source-tabs-wrap").hide();
        currentTopicCategory = "";
    } else {
        $("#source-tabs-wrap").show();
        var topicKey = (topic || "").toString().trim().toLowerCase();
        currentTopicCategory = CATEGORY_FILTER[topic] || '';
    }

    currentSearchParams = paramsFromTab($(this));
    nextPageUrl = null;
    //debug
    console.log('Topic tab clicked:', topic || 'Popular', '| params:', JSON.stringify(currentSearchParams));
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

    currentSearchParams = {
        q: 'site:' + domain,
        language: '',
        country: '',
        category: currentTopicCategory
    };
    nextPageUrl = null;

    console.log('Source tab clicked:', sourceKey, '| params:', JSON.stringify(currentSearchParams));
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