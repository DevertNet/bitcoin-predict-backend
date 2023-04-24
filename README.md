# bitcoin-predict-backend

Fetch news articles and rate them with the help of chatgpt. Provide a api for bitcoin-predict-frontend (https://github.com/DevertNet/bitcoin-predict-frontend).

tl/dr: A little further down I have documented my results with the tool. Maybe that's enough for one or the other ;)

# Idea of this project

I had my idea for the project before a few years. My idea was that you can predict the Bitcoin price with the help of news. The thesis was: if there is a lot of positive news, then the price will rise in the next few days. If there is more negative news, then it sings. Since ChatGPT is a tool that can be used to rate text very well, I have now implemented it.
Due to other things, I lost sight of that a bit. But after a researcher confirmed the whole thing for shares a few days ago, I was fired up again.

# License

If you make money with this method. It would be great if you gave me some of your profit. I have not been able to use the method successfully so far. I assume no responsibility for anything.

# Requirements

## Tech

- PHP 8.1
- MariaDB 10.4 (MySQL is not working because of the error `SQLSTATE[42000]: Syntax error or access violation: 1170 BLOB/TEXT column 'url' used in key specification without a key length`)

## APIs

- TheNewsApi Subscription (19$ per month)
- OpenAi Account with billing details (around 19$ / 50k news)
- Simlarweb Account (Free Version)

The analyses of 3,5 month costs around 40 dollar.
First i used the Mediastack API to fetch news. But the rate limit is to low and the quality of TheNewsApi is better.

# Install

I have only brought the whole project so far that it runs locally. If you want to install it on a server, further steps may be necessary.

1. `composer install`
2. Update `.env` file
3. `bin/console doctrine:database:create`
4. `bin/console doctrine:schema:create`
5. `bin/console doctrine:migrations:migrate`
6. See Usage
7. Install https://github.com/DevertNet/bitcoin-predict-frontend to see results in a graph

Webserver should use `/public` as doc root. But you can use ddev (config included) or maybe `bin/console server:start` to launch that thing for the frontend.

# Usage

Fetch news into database. News will be fetched per day. Every API Request to the news api will be cached in the database. But not the actual day. So this command can safely fired multiple times without run in api limitations.
Today:
`./bin/console app:fetch-news`
Specific range:
`./bin/console app:fetch-news 2023-04-01 2023-01-01`

Update the csv with domain popularity infos. This should be used after `app:fetch-news`. The command will put all domains from the fetched news in a csv and also fetch the simlarweb Global Rank. The simlarweb API will be only called once per domain. So you can safly fired multiple times.
`./bin/console app:update-popularity-csv`

Then rate the news for a given method. Several processes can be executed in parallel, as a random news item is always evaluated. Currently, the news that have not yet been rated are drawn randomly from the database as a list. However, this list is only drawn at the beginning and does not update itself. Therefore, the instances should be restarted regularly so that the lists are refreshed and if there are less than 1000 remainig news, only one instance should run.
`./bin/console app:predict-rating-v1`
`./bin/console app:predict-rating-v2`

# Rating Method for PredictRatingV2

## Method

- Every news will be rated between -10 and 10; -10 is bad; 10 is good
- Ask chatgpt for rate the news title: positive = 10, negative=-10 and neutral=0
- Add popularity based on source. I used the Global Ranking from https://www.similarweb.com/. The rank (3000000 to 1) will be transformed to 0.1 and 1. The transformed value will be multiplied with the inital score from chatgpt.
- Used news scope:
  - Hint: Switched to TheNewsApi, because i reached the limit at mediastack. Plus the historical data not work. Need to set some filters, because the amount of ALL news (more than 1 000 000) is to height...so i filter the news sources to some domains. So we get around 40k of news for this scope.
  - Date: 2023-01-01 to 2023-04-17
  - api: thenewsapi.com
  - languages: en
  - categories: general,business,tech,politics
  - exclude_categories: sports
  - domain: nytimes.com,cnn.com,bbc.co.uk,theguardian.com
  - search: -sport+-museums+-football+-rugby+-Bundesliga+-Premier
  - search_fields: title,main_text,description,keywords
  - url: don't import news with string 'sport' in url, because the categories are not 100% perfect

## Prompt

Forget all your previous instructions. Pretend you are a financial and crypto expert. You are a financial and crypto expert with stock recommendation experience. Answer “YES” if good news, “NO” if bad news, or “UNKNOWN” if uncertain in the first line. Don't answer more than that. Headline:

## Conclusion

![](readme-v1.png)

The result now looks more promising. For example, clear spikes can be seen on 19.01.2023 or 23.03.2023. The following day, the price goes up. But there are also days with spikes and the price drops the next day.
After some deeper analyses, however, I was unfortunately unable to develop an algorithm that guarantees a profit. Especially in the range 14.08.2022 - 14.12.2022, in which the price tends to fall, significant losses were made.
One reason for this could be that partly irrelevant news from ChatGPT is positively evaluated, e.g. "Manchester's Caribbean Carnival returns with 50th anniversary celebrations".
The inclusion of the popularity can now almost be dispensed with, as the news is only drawn from very popular domains. However, the function can't hurt if you need it again in the future.

## Recommendation and ideas for v3

- Add Twitter hashtag #bitcoin ranking/usage to the score.
- Scrape the content of the news and include them in the ChatGPT rating. But i think because of the big amount of news this make no sense.
- Implement real multithreading for `./bin/console app:predict-rating-v3`. Every prediction need around 1s. 40k news need 11 to 12 hours...see `Usage` section for more informations.
- Adjust the prompt to exclude unrelevant news like "Manchester's Caribbean Carnival returns with 50th anniversary celebrations"

# Rating Method for PredictRatingV1

If you want to use this method, please adjust the `FetchNewsCommand.php` regarding to the news scope. Delete all news before this. I don't want a perfect tool, im just interessted in the results. Sorry for my lazyness :D

## Method

- Every news will be rated between -10 and 10; -10 is bad; 10 is good
- Ask chatgpt for rate the news title: positive = 10, negative=-10 and neutral=0
- Add popularity based on source. I used the Global Ranking from https://www.similarweb.com/website/theage.com.au/#overview. The rank (3000000 to 1) will be transformed to 0.5 and 1. The transformed value will be multiplied with the inital score from chatgpt.
- Used news scope:
  - api: mediastack.com
  - languages: en
  - categories: technology,business
  - keywords: crypto, bitcoin

## Prompt

Forget all your previous instructions. Pretend you are a financial expert. You are a financial expert with stock recommendation experience. Answer “YES” if good news, “NO” if bad news, or “UNKNOWN” if uncertain in the first line. Then elaborate with one short and concise sentence on the next line. Headline:

## Conclusion

![](readme-v1.png)
Unfortunately, the Mediastack API has a bug, which is why only news from the last 3 months are available. Despite paid tariff. Overall, I can't see any connection between news and the chart.
The chart shows entries per calendar day. The news scores are simply added up per calendar day.
The problem why it can't work is perhaps the too small selection of news.

## Recommendation and ideas for v2

- Add more news. I think all news that can also influence the stock market is also relevant for Bitcoin. Don't filter the news for keywords.
- Switch to another news api, if mediastack cannot provide historical news.
- Scrape the content of the news and include dem in the ChatGPT rating.
- Make the prompt more bitcoin-based. ChatGpt may therefore not rate the news in relation to bitcoin.
- Change the popularity ratio to 0.1 to 1. This will give more impact for the popularity.

# PHPUnit

PHPUnit can use the normal database, because its self cleaning. There is NO `_test` suffix for the db.

`composer test`
