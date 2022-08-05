export const redirectTo = async (router, to) => {
  try {
    await router.push(to)
  } catch (error) {
    if (
      !(
        error.name === 'NavigationDuplicated' ||
        error.message.includes(
          'Avoided redundant navigation to current location'
        )
      )
    ) {
      throw error
    }
  }
}
