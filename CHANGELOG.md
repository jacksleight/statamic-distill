# Changelog

## 0.8.0 (2024-07-12)

- [new] Ability to fetch only unique items

## 0.7.2 (2024-06-30)

- [fix] Bard fields with string values by @edalzell

## 0.7.1 (2024-06-17)

- [fix] Extract bard/text augmentation regression with Statamic 4

## 0.7.0 (2024-05-28)

- [fix] Add some resiliency by @edalzell
- [new] Blade/PHP only distill_text modifier

## 0.6.4 (2024-05-10)

- [fix] Augmentation error with Statamic 5

## 0.6.3 (2024-05-07)

- Statamic 5 support

## 0.6.2 (2024-04-08)

- [fix] Support for `Values` objects

## 0.6.1 (2023-11-08)

- [fix] Error when no Distill searchables are configured

## 0.6.0 (2023-10-30)

- Distill is now completely free and MIT licenced!

## 0.5.0 (2023-08-18)

- [new] {{ distill:text }} tag that extracts plain text
- [fix] Fix search key mapping error

## 0.4.3 (2023-08-14)

- [fix] Error when distilling sets that have no fields

## 0.4.2 (2023-06-08)

- [fix] Error using `distill:bard` with an empty Bard field

## 0.4.1 (2023-06-05)

- [fix] Using `distill:bard` with a Bard field as the source

## 0.4.0 (2023-05-31)

- [new] Blade/PHP only distill_bard modifier
- [break] Removed `is_distilled` variable, use `result_type` instead

## 0.3.1 (2023-05-29)

- [fix] Missing edition data

## 0.3.0 (2023-04-24)

- [break] Expanded `entry`, `term` and `asset` type values

## 0.2.0 (2023-04-18)

- [new] Add `Distill:bard()` method
- [break] Renamed `Distill:from()` method to `Distill:query()`

## 0.1.1 (2023-04-05)

- [fix] Undefined array key error when no Stills exist
- [fix] Unknown StatusQueryBuilder object error

## 0.1.0 (2023-03-29)

- Initial release 🚀
