-- Получить минимальную, максимальную и среднюю цену за километр всего транспорта грузоподъемностью более 20 тонн.
select min(transports.price) from transports where transports.load_capacity > 20;
select max(transports.price) from transports where transports.load_capacity > 20;
select avg(transports.price) from transports where transports.load_capacity > 20;

-- Выбрать наземный транспорт, максимальная скорость которого больше, чем 70км/ч.
select * from transports where transports.type = 'ground_transport' and transports.full_speed > 70;

-- Выбрать категорию с наибольшим количеством транспорта.
WITH transpotr_count as (select count(*) as length, transports.type from transports group by transports.type)
select type from transpotr_count where length = (select max(length) from transpotr_count);

-- Выбрать категории, в которых количество транспорта, грузоподъемность которого более 15 тонн, превышает количество транспорта, грузоподъемность которого менее 15 тонн.
select 
	categories.type
from 
	(select type from transports group by transports.type) as categories
left join
	(select type, count(*) as length from transports where load_capacity > 15 group by transports.type) as more
on
	categories.type = more.type
left join
	(select type, count(*) as length from transports where load_capacity < 15 group by transports.type) as less
on
	categories.type = less.type
where
	more.length > less.length;