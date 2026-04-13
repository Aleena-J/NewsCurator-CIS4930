var PROXY = "../backend/api/news_proxy.php";

var CATEGORY_FILTER = {
    sport: "sport",
    politics: "politics",
    environment: "environment",
    health: "health",
    education: "education",
    science_tech: '"Science and Technology"',
    economy: '"Economy, Business and Finance"',
    crime: '"Crime, Law and Justice"',
    human_interest: '"Human Interest"',
    war: '"War, Conflict and Unrest"',
};

var currentSearchParams = null;
var nextPageUrl = null;


function buildSearchParams() {
    var kw = $("#search-keywords").val().trim();
    var country = $("#filter-country").val();
    var lang = $("#filter-lang").val();
    var catKey = $("#filter-category").val();

    return {
        q: kw !== "" ? 'text:"' + kw + '"' : "",
        keywords: kw,
        country: country || "",
        language: lang || "",
        category: catKey && CATEGORY_FILTER[catKey] ? CATEGORY_FILTER[catKey] : "",
    };
}

// gets html for the text snippet
function snippetDisplayHtml(post) {
    if (post.highlightText) {
        return post.highlightText;
    }
    var plain = post.text || post.summary || "";
    return plain ? $("<div>").text(plain).html() : "";
}

function articleDate(post) {
    if (post.published) {
        return post.published;
    }
    if (post.thread && post.thread.published) {
        return post.thread.published;
    }
    if (post.crawled) {
        return post.crawled;
    }
    return "";
}

function articlePageQuery(post) {
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
    p.set("date", articleDate(post));
    p.set("language", post.language || "");
    p.set(
        "image",
        post.thread && post.thread.main_image ? post.thread.main_image : ""
    );
	p.set("description", post.text || post.description || "");
    return "article.php?" + p.toString();
}

function formatArticleDate(isoStr) {

    var d = new Date(isoStr);
    if (isNaN(d.getTime())) {
        return isoStr;
    }
    return d.toLocaleString(undefined, {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
    });
}

function makeCard(post) {
    var title = post.title || "No title";
    var url = post.url || "#";
    var source  = (post.thread && post.thread.site_full) ? post.thread.site_full : "";
    var image   = (post.thread && post.thread.main_image) ? post.thread.main_image : "";
    var snippet = snippetDisplayHtml(post);
    var date = articleDate(post);

    var imageHtml;
    if (image != "") {
        imageHtml = "<img src='" + image + "' alt='article image' onerror=\"this.outerHTML='<div class=no-image>No image available</div>'\">";
    } else {
        imageHtml = "<div class='no-image'>No image available</div>";
    }

    var card = '<li class="search-result-card">';
    card += '<div class="search-result-image">' + imageHtml + "</div>";
    card += '<div class="search-result-main">';
    card +=
        '<a class="search-result-title" href="' +
        articlePageQuery(post) +
        '">' +
        title +
        "</a>";
    if (source) {
        card +=
            '<div class="search-result-meta">' + source
        if (date) {
            var formattedDate = formatArticleDate(date);
            if (source) {
                card += " | ";
            }
            card += '<time datetime="' + date.replace(/"/g, "&quot;") + '">';
            card += $("<span>").text(formattedDate).html();
            card += "</time>";
        }
        card += "</div>";
    }
    if (snippet) {
        card += '<p class="search-result-snippet">' + snippet + "</p>";
    }
    card +=
        '<a class="search-result-link" href="' + url + '" target="_blank">Read full article &rarr;</a>';
    card += "</div></li>";
    return card;
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
    var url;
    if (append && nextPageUrl != null) {
        url = PROXY + "?next=" + encodeURIComponent(nextPageUrl);
    } else {
        url = buildProxyUrl(params);
    }

    if (!append) {
        $("#search-results-list").html(
            "<li class='loading-msg'>Loading…</li>"
        );
        $("#search-load-more-btn").hide();
    }

    $.ajax({
        url: url,
        method: "GET",
        dataType: "json",
        success: function (data) {
            var posts = data.posts;
            nextPageUrl = data.next;

            $("#search-results-info").text(data.totalResults + " articles found");

            if (posts.length === 0) {
                $("#search-results-list").html(
                    "<li class='empty-msg'>No articles found. Try different keywords or filters.</li>"
                );
                return;
            }

            var html = "";
            for (var i = 0; i < posts.length; i++) {
                html += makeCard(posts[i]);
            }

            if (append) {
                $("#search-results-list").append(html);
            } else {
                $("#search-results-list").html(html);
            }

            if (nextPageUrl != null) {
                $("#search-load-more-btn").show();
            } else {
                $("#search-load-more-btn").hide();
            }
        },
        error: function () {
            $("#search-results-list").html(
                "<li class='error-msg'>Could not load results. Check the API key and network.</li>"
            );
        },
    });
}

$("#search-form").on("submit", function (e) {
    e.preventDefault();
    var params = buildSearchParams();
    var hasAnyFilter =
        params.q !== "" ||
        params.country !== "" ||
        params.language !== "" ||
        params.category !== "";
    if (!hasAnyFilter) {
        $("#search-results-info").text("Enter keywords and/or pick at least one filter.");
        $("#search-results-list").empty();
        return;
    }
    if (params.q.length > 100) {
        $("#search-results-info").text(
            "Max keyword length is 93 characters. Yours is " + params.keywords.length + "."
        );
        $("#search-results-list").empty();
        nextPageUrl = null;
        $("#search-load-more-btn").hide();
        return;
    }
    if (!params.q || params.q.trim() === "") {
        params.q = "domain_rank:<2000";
    }
    currentSearchParams = params;
    nextPageUrl = null;
    loadNews(currentSearchParams, false);
});

$("#search-load-more-btn").on("click", function () {
    loadNews(currentSearchParams, true);
});
