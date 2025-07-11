import { Div } from "@base-framework/atoms";
import { Skeleton } from "@base-framework/ui/atoms";

/**
 * UserSkeleton
 *
 * A 1:1 skeleton of the ProfilePage layout:
 * – cover image
 * – sidebar (avatar, name, role, dept, contact, icons)
 * – main content sections (About, Work Progress, Projects, Skills, Reviews)
 *
 * @returns {object}
 */
export const UserSkeleton = () =>
{
	// sidebar pieces
	const avatarSkeleton = Skeleton({ width: "w-60", height: "h-60", shape: "circle", class: "mx-auto mt-6" });
	const nameSkeleton = Skeleton({ width: "w-1/2", height: "h-8", class: "mx-auto" });
	const roleSkeleton = Skeleton({ width: "w-1/3", height: "h-5", class: "mx-auto" });
	const deptSkeleton = Skeleton({ width: "w-1/4", height: "h-5", class: "mx-auto" });
	const emailSkeleton = Skeleton({ width: "w-2/3", height: "h-4", class: "mx-auto mt-2" });
	const phoneSkeleton = Skeleton({ width: "w-1/3", height: "h-4", class: "mx-auto" });
	const iconButtonsSkeleton = Div({ class: "flex justify-center space-x-4 mt-4" },
		Array(4).fill().map(() => Skeleton({ shape: "circle", width: "w-8", height: "h-8" }))
	);

	const sidebarSkeleton = Div({ class: "w-full lg:w-1/3 space-y-4" }, [
		avatarSkeleton,
		nameSkeleton,
		roleSkeleton,
		deptSkeleton,
		emailSkeleton,
		phoneSkeleton,
		iconButtonsSkeleton
	]);

	// About section
	const aboutSectionSkeleton = Div({ class: "space-y-6" }, [
		Skeleton({ width: "w-24", height: "h-6", class: "rounded-md" }),
		Skeleton({ width: "w-full", height: "h-24", class: "rounded-md" })
	]);

	// Work Progress section
	const workProgressSectionSkeleton = Div({ class: "space-y-4" }, [
		Skeleton({ width: "w-32", height: "h-6", class: "rounded-md" }),
		Div({ class: "pl-6 space-y-6" },
			[0, 1].map(() =>
				Div({ class: "flex items-center space-x-4" }, [
					Skeleton({ width: "w-5", height: "h-5", class: "rounded-full" }),
					Div({ class: "space-y-1" }, [
						Skeleton({ width: "w-1/3", height: "h-4", class: "rounded-md" }),
						Skeleton({ width: "w-1/4", height: "h-3", class: "rounded-md" })
					])
				])
			)
		)
	]);

	// Projects section
	const projectsSectionSkeleton = Div({ class: "space-y-4" }, [
		Skeleton({ width: "w-36", height: "h-6", class: "rounded-md" }),
		Div({ class: "overflow-x-auto" }, [
			Div({ class: "min-w-full divide-y divide-muted-200" }, [
				Div({ class: "grid grid-cols-3 gap-4 pb-2" },
					Array(3).fill().map(() => Skeleton({ width: "w-full", height: "h-4", class: "rounded-md" }))
				),
				...[0, 1].map(() =>
					Div({ class: "grid grid-cols-3 gap-4 py-3" }, [
						Skeleton({ width: "w-2/3", height: "h-4", class: "rounded-md" }),
						Skeleton({ width: "w-1/2", height: "h-4", class: "rounded-md" }),
						Skeleton({ width: "w-1/3", height: "h-6", class: "rounded-full" })
					])
				)
			])
		])
	]);

	// Skills section
	const skillsSectionSkeleton = Div({ class: "space-y-4" }, [
		Skeleton({ width: "w-20", height: "h-6", class: "rounded-md" }),
		Div({ class: "flex flex-wrap gap-2" },
			Array(5).fill().map(() => Skeleton({ width: "w-20", height: "h-6", class: "rounded-full" }))
		)
	]);

	// Reviews section
	const reviewsSectionSkeleton = Div({ class: "space-y-4" }, [
		Skeleton({ width: "w-40", height: "h-6", class: "rounded-md" }),
		Skeleton({ width: "w-1/3", height: "h-4", class: "rounded-md" }),
		Div({ class: "overflow-x-auto" }, [
			Div({ class: "min-w-full divide-y divide-muted-200" }, [
				Div({ class: "grid grid-cols-3 gap-4 pb-2" },
					Array(3).fill().map(() => Skeleton({ width: "w-full", height: "h-4", class: "rounded-md" }))
				),
				...[0, 1].map(() =>
					Div({ class: "grid grid-cols-3 gap-4 py-3" }, [
						Skeleton({ width: "w-1/4", height: "h-4", class: "rounded-md" }),
						Skeleton({ width: "w-1/3", height: "h-4", class: "rounded-md" }),
						Skeleton({ width: "w-1/5", height: "h-4", class: "rounded-md" })
					])
				)
			])
		])
	]);

	// main content
	const mainContentSkeleton = Div({ class: "flex-1 space-y-12 mt-6 px-6" }, [
		aboutSectionSkeleton,
		workProgressSectionSkeleton,
		projectsSectionSkeleton,
		skillsSectionSkeleton,
		reviewsSectionSkeleton
	]);

	// full layout
	return Div({ class: "p-6 pt-0 2xl:mx-auto w-full 2xl:max-w-[1600px] space-y-8" }, [
		Skeleton({ width: "w-20", height: "h-6", class: "rounded-md" }),
		Div({ class: "flex flex-col lg:flex-row lg:space-x-8" }, [
			sidebarSkeleton,
			mainContentSkeleton
		])
	]);
};

export default UserSkeleton;