import menuItems from "../vertical-nav-menu/navMenuItems";

export default {
    pages: {
        key: "title",
        data: menuItems.map(item => ({
            title: item.name,
            slug: item.slug,
            url: item.url,
            icon: item.icon,
            is_bookmarked: false
        }))
    }
};
