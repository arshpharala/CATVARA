$(document).ready(function () {
    let currentPath = window.location.pathname.replace(/\/+$/, ""); // Remove trailing slash
    let baseUrl = window.location.origin;

    // Normalize and loop through all sidebar links
    $(".nav-sidebar a").each(function () {
        let link = $(this).attr("href");

        // Skip invalid or JS-based links
        if (!link || link === "#" || link.startsWith("javascript")) return;

        // Convert relative URLs to absolute for consistent comparison
        if (!link.startsWith("http")) {
            link = baseUrl + link;
        }

        let linkPath = new URL(link).pathname.replace(/\/+$/, "");

        // If current path starts with the link path, mark as active
        if (currentPath === linkPath || currentPath.startsWith(linkPath)) {
            // Mark this link as active
            $(this).addClass("active");

            // Open all parent treeview menus
            $(this)
                .parentsUntil(
                    ".nav-sidebar",
                    ".nav-item.has-treeview, .nav-item"
                )
                .addClass("menu-open");

            // Also activate parent <a> links
            $(this)
                .parentsUntil(".nav-sidebar", ".nav-item")
                .children("a.nav-link")
                .addClass("active");
        }
    });
});
