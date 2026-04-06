<details class="tw-dw-dropdown tw-dw-dropdown-end">
    <summary class="tw-bg-transparent tw-text-white tw-font-bold tw-text-sm md:tw-text-base select-none tw-cursor-pointer tw-list-none">
        🌐 {{ isset($_GET['lang']) ? config('constants.langs')[$_GET['lang']]['full_name'] : config('constants.langs')[config('app.locale')]['full_name'] }}
    </summary>
    <ul class="tw-p-2 tw-shadow tw-dw-menu tw-dw-dropdown-content tw-z-[100] tw-w-48 tw-bg-white tw-rounded-xl tw-mt-3">
        @foreach (config('constants.langs') as $key => $val)
            <li>
                <a value="{{ $key }}" class="change_lang tw-text-gray-800 hover:tw-bg-gray-100"> 
                    {{ $val['full_name'] }}
                </a>
            </li>
        @endforeach
    </ul>
</details>