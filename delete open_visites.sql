SELECT * FROM visitors_visits
join users on users.id = inspector_id
where status = 'in_progress'

select * from users

SELECT * FROM visitors_visits
where status = 'in_progress'
and inspector_id not in (34 , 1  )


select * from visitors_visit_items
where visit_id in (
	SELECT id FROM visitors_visits
	where status = 'in_progress'
	and inspector_id not in (34 , 1  )
)

select * from visitors_visit_photos
where visit_id in (
	SELECT id FROM visitors_visits
	where status = 'in_progress'
	and inspector_id not in (34 , 1  )
)

select * from visitors_violation_follow_ups
where visit_id in (
	SELECT id FROM visitors_visits
	where status = 'in_progress'
	and inspector_id not in (34 , 1  )
)


delete from visitors_visit_photos
where visit_id in (
	SELECT id FROM visitors_visits
	where status = 'in_progress'
	and inspector_id not in (34 , 1  )
)

delete from visitors_violation_follow_ups
where visit_id in (
	SELECT id FROM visitors_visits
	where status = 'in_progress'
	and inspector_id not in (34 , 1  )
)


delete from visitors_visit_items
where visit_id in (
	SELECT id FROM visitors_visits
	where status = 'in_progress'
	and inspector_id not in (34 , 1  )
)

delete FROM visitors_visits
where status = 'in_progress'
and inspector_id not in (34 , 1  )

