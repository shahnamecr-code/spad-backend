export interface IAboutResData {
    user_default_profile: string,
    about_experts_list: [
        {
            name: string,
            mobile: string
        },
        {
            name: string,
            image: string,
            mobile: string
        }
    ],
    about_image: string,
    about_us_title: string,
    about_us: string,
    experts_count: number,
    regions_count: number,
    users_count: number,
    ads_count: number
}

